<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Models\Variavel;
use App\Models\Municipio;
use App\Models\DadoCubo;
use App\Models\CorrelacaoVariavel;

// Funções matemáticas do namespace global (necessário em namespaces PHP)
use function abs;
use function array_fill;
use function array_map;
use function array_sum;
use function count;
use function exp;
use function implode;
use function log;
use function min;
use function round;
use function sort;
use function sqrt;
use function usort;

/**
 * Artisan Command: calcular:correlacoes
 *
 * Popula a tabela `correlacoes_variaveis` (Graph Layer — Camada 2)
 * calculando os coeficientes de Pearson e Spearman entre todos os pares
 * de variáveis do cubo, com suporte a lag temporal configurável.
 *
 * Uso:
 *   php artisan calcular:correlacoes
 *   php artisan calcular:correlacoes --municipio=1
 *   php artisan calcular:correlacoes --lag-max=3
 *   php artisan calcular:correlacoes --p-max=0.1
 *   php artisan calcular:correlacoes --force
 */
class CalcularCorrelacoes extends Command
{
    protected $signature = 'calcular:correlacoes
                            {--municipio= : ID do município (opcional; processa todos se omitido)}
                            {--lag-max=2  : Lag máximo em anos a ser calculado (padrão: 2)}
                            {--p-max=0.05 : P-valor máximo para salvar a correlação (padrão: 0.05)}
                            {--force      : Recalcula e sobrescreve correlações já existentes}';

    protected $description = 'Calcula correlações de Pearson/Spearman entre variáveis do cubo e popula o Graph Layer (tabela correlacoes_variaveis)';

    public function handle(): int
    {
        $lagMax   = (int)   $this->option('lag-max');
        $pMax     = (float) $this->option('p-max');
        $force    = (bool)  $this->option('force');
        $munId    = $this->option('municipio') ? (int) $this->option('municipio') : null;

        $this->info('🔗 Projeto Trajetórias — Cálculo do Graph Layer');
        $this->info("   Lag máximo: {$lagMax} anos | P-valor máximo: {$pMax}");
        $this->newLine();

        // 1. Carrega municípios e variáveis
        $municipios = $munId
            ? Municipio::where('id', $munId)->get()
            : Municipio::all();

        $variaveis = Variavel::with('eixo')->get();

        if ($municipios->isEmpty()) {
            $this->error('Nenhum município encontrado.');
            return self::FAILURE;
        }

        if ($variaveis->count() < 2) {
            $this->error('São necessárias ao menos 2 variáveis para calcular correlações.');
            return self::FAILURE;
        }

        $this->info("📍 Municípios: {$municipios->count()} | 📊 Variáveis: {$variaveis->count()}");

        // 2. Gera todos os pares de variáveis (combinações sem repetição)
        $pares = [];
        $vars  = $variaveis->values();
        for ($i = 0; $i < $vars->count(); $i++) {
            for ($j = $i + 1; $j < $vars->count(); $j++) {
                $pares[] = [$vars[$i], $vars[$j]];
            }
        }

        $totalPares = count($pares) * ($lagMax + 1);
        $this->info("🔢 Pares a calcular: " . count($pares) . " × " . ($lagMax + 1) . " lags = {$totalPares} correlações");
        $this->newLine();

        $bar = $this->output->createProgressBar($municipios->count() * $totalPares);
        $bar->start();

        $salvos     = 0;
        $descartados = 0;

        foreach ($municipios as $municipio) {
            // 3. Carrega todos os dados do município de uma vez (evita N+1)
            $dadosMunicipio = DadoCubo::where('municipio_id', $municipio->id)
                ->with('variavel')
                ->get()
                ->groupBy('variavel_id');

            foreach ($pares as [$varA, $varB]) {
                $serieA = $dadosMunicipio->get($varA->id);
                $serieB = $dadosMunicipio->get($varB->id);

                if (!$serieA || !$serieB) {
                    $bar->advance($lagMax + 1);
                    continue;
                }

                // Indexa por ano
                $mapaA = $serieA->keyBy('ano_periodo');
                $mapaB = $serieB->keyBy('ano_periodo');

                for ($lag = 0; $lag <= $lagMax; $lag++) {
                    $bar->advance();

                    // 4. Alinha as séries com o lag temporal
                    [$xSerie, $ySerie] = $this->alinharSeriesComLag($mapaA, $mapaB, $lag);

                    if (count($xSerie) < 3) {
                        // Mínimo de 3 pares para correlação estatisticamente válida
                        $descartados++;
                        continue;
                    }

                    // 5. Calcula Pearson e Spearman
                    $pearson  = $this->pearson($xSerie, $ySerie);
                    $spearman = $this->spearman($xSerie, $ySerie);
                    $pValor   = $this->pValorAproximado($pearson, count($xSerie));
                    $n        = count($xSerie);

                    // 6. Descarta correlações não significativas (economiza espaço)
                    if ($pValor > $pMax) {
                        $descartados++;
                        continue;
                    }

                    // 7. Calcula o peso da aresta para o grafo
                    $pesoGrafo = abs($pearson) * (1 - $pValor);

                    // 8. Determina direção
                    $direcao = $this->determinarDirecao($pearson);

                    // 9. Tipo de relação (cruzamento de eixos)
                    $tipoRelacao = $this->tipoRelacao($varA, $varB);

                    // 10. Período analisado
                    $anosA = $mapaA->keys()->sort();
                    $periodoInicio = $anosA->first();
                    $periodoFim    = $anosA->last();

                    // 11. Upsert (insere ou atualiza se --force)
                    $chave = [
                        'variavel_a_id' => $varA->id,
                        'variavel_b_id' => $varB->id,
                        'municipio_id'  => $municipio->id,
                        'lag_anos'      => $lag,
                    ];

                    if ($force || !CorrelacaoVariavel::where($chave)->exists()) {
                        CorrelacaoVariavel::updateOrCreate($chave, array_merge($chave, [
                            'periodo_inicio' => $periodoInicio,
                            'periodo_fim'    => $periodoFim,
                            'coef_pearson'   => round($pearson, 6),
                            'coef_spearman'  => round($spearman, 6),
                            'p_valor'        => round($pValor, 6),
                            'n_amostras'     => $n,
                            'direcao'        => $direcao,
                            'peso_grafo'     => round($pesoGrafo, 6),
                            'tipo_relacao'   => $tipoRelacao,
                            'metadados'      => [
                                'metodo'          => 'pearson+spearman',
                                'versao_pipeline' => '1.0',
                                'calculado_em'    => now()->toISOString(),
                            ],
                        ]));
                        $salvos++;
                    }
                }
            }

            // Correlações regionais (municipio_id = null) — média entre municípios
            // (calculada em passagem separada após todos os municípios)
        }

        $bar->finish();
        $this->newLine(2);

        // 12. Calcula correlações regionais agregadas (sem municipio_id)
        $this->info('🌍 Calculando correlações regionais agregadas...');
        $this->calcularCorrelacoesRegionais($pares, $lagMax, $pMax, $force, $salvos, $descartados);

        $this->newLine();
        $this->table(
            ['Resultado', 'Total'],
            [
                ['✅ Correlações salvas',     $salvos],
                ['🚫 Descartadas (p > max)', $descartados],
            ]
        );

        $this->newLine();
        $this->info('✅ Graph Layer populado com sucesso!');
        $this->info('   Execute agora: php artisan gerar:features-ml');

        return self::SUCCESS;
    }

    // ── Métodos estatísticos ──────────────────────────────────────────────────

    /**
     * Alinha duas séries temporais com um lag em anos.
     * Ex: lag=2 → X[2010] é pareado com Y[2012]
     *
     * @return array{float[], float[]}
     */
    private function alinharSeriesComLag($mapaA, $mapaB, int $lag): array
    {
        $xSerie = [];
        $ySerie = [];

        foreach ($mapaA as $ano => $dadoA) {
            $anoTarget = (string)((int)$ano + $lag);
            if ($mapaB->has($anoTarget)) {
                $xSerie[] = (float) $dadoA->valor;
                $ySerie[] = (float) $mapaB[$anoTarget]->valor;
            }
        }

        return [$xSerie, $ySerie];
    }

    /**
     * Coeficiente de correlação de Pearson (correlação linear).
     * Adequado quando as variáveis seguem distribuição normal.
     */
    private function pearson(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) return 0.0;

        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;

        $cov = 0.0;
        $varX = 0.0;
        $varY = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $dx   = $x[$i] - $meanX;
            $dy   = $y[$i] - $meanY;
            $cov  += $dx * $dy;
            $varX += $dx * $dx;
            $varY += $dy * $dy;
        }

        $denom = sqrt($varX * $varY);
        return $denom > 0 ? ($cov / $denom) : 0.0;
    }

    /**
     * Coeficiente de correlação de Spearman (correlação de postos).
     * Mais robusto para dados não-normais (típico de dados ambientais e epidemiológicos).
     */
    private function spearman(array $x, array $y): float
    {
        $n = count($x);
        if ($n < 2) return 0.0;

        // Converte valores para postos (ranks)
        $rankX = $this->rank($x);
        $rankY = $this->rank($y);

        return $this->pearson($rankX, $rankY);
    }

    /**
     * Converte um array de valores em postos (ranks) para o cálculo de Spearman.
     * Empates recebem o posto médio.
     */
    private function rank(array $values): array
    {
        $n = count($values);
        $indexed = array_map(null, $values, range(0, $n - 1));

        usort($indexed, fn($a, $b) => $a[0] <=> $b[0]);

        $ranks = array_fill(0, $n, 0.0);
        $i = 0;

        while ($i < $n) {
            $j = $i;
            // Agrupa empates
            while ($j < $n - 1 && $indexed[$j][0] == $indexed[$j + 1][0]) {
                $j++;
            }
            // Posto médio para empates
            $meanRank = ($i + $j) / 2 + 1;
            for ($k = $i; $k <= $j; $k++) {
                $ranks[$indexed[$k][1]] = $meanRank;
            }
            $i = $j + 1;
        }

        return $ranks;
    }

    /**
     * Aproximação do p-valor usando a distribuição t de Student (bicaudal).
     * t = r × sqrt((n-2) / (1 - r²))
     */
    private function pValorAproximado(float $r, int $n): float
    {
        if ($n <= 2) return 1.0;
        if (abs($r) >= 1.0) return 0.0;

        $t  = $r * sqrt(($n - 2) / (1 - $r * $r));
        $df = $n - 2;

        // Aproximação da distribuição t via integração numérica (série de Abramowitz)
        return $this->pValorT(abs($t), $df);
    }

    /**
     * P-valor da distribuição t (bicaudal) — aproximação numérica.
     */
    private function pValorT(float $t, int $df): float
    {
        // Regularized incomplete beta function B(x; a, b) para p-valor da dist. t
        $x = $df / ($df + $t * $t);
        $a = $df / 2.0;
        $b = 0.5;

        return $this->incompleteBeta($x, $a, $b);
    }

    /**
     * Função beta incompleta regularizada I_x(a,b) via expansão em fração contínua.
     * Usada como aproximação para p-valor t.
     */
    private function incompleteBeta(float $x, float $a, float $b): float
    {
        if ($x <= 0.0) return 0.0;
        if ($x >= 1.0) return 1.0;

        $lbeta = $this->lnGamma($a) + $this->lnGamma($b) - $this->lnGamma($a + $b);
        $factor = exp(log($x) * $a + log(1 - $x) * $b - $lbeta) / $a;

        // Expansão em fração contínua de Lentz
        $maxIter = 200;
        $eps     = 1e-8;
        $fpmin   = 1e-30;

        $qab = $a + $b;
        $qap = $a + 1.0;
        $qam = $a - 1.0;
        $c   = 1.0;
        $d   = 1.0 - $qab * $x / $qap;

        if (abs($d) < $fpmin) $d = $fpmin;
        $d   = 1.0 / $d;
        $h   = $d;

        for ($m = 1; $m <= $maxIter; $m++) {
            $m2 = 2 * $m;

            $aa = $m * ($b - $m) * $x / (($qam + $m2) * ($a + $m2));
            $d  = 1.0 + $aa * $d;
            $c  = 1.0 + $aa / $c;
            if (abs($d) < $fpmin) $d = $fpmin;
            if (abs($c) < $fpmin) $c = $fpmin;
            $d  = 1.0 / $d;
            $h  *= $d * $c;

            $aa = -($a + $m) * ($qab + $m) * $x / (($a + $m2) * ($qap + $m2));
            $d  = 1.0 + $aa * $d;
            $c  = 1.0 + $aa / $c;
            if (abs($d) < $fpmin) $d = $fpmin;
            if (abs($c) < $fpmin) $c = $fpmin;
            $d  = 1.0 / $d;
            $del = $d * $c;
            $h  *= $del;

            if (abs($del - 1.0) < $eps) break;
        }

        return min(1.0, $factor * $h);
    }

    /**
     * Logaritmo natural da função Gamma — implementação própria via série de Lanczos.
     * Substitui lgamma() do C (não disponível em todas as instalações PHP).
     * Precisão: erro < 5e-11 para z > 0.
     *
     * Referência: Numerical Recipes in C, cap. 6.1
     */
    private function lnGamma(float $z): float
    {
        // Coeficientes de Lanczos (g=7, n=9)
        $coef = [
             0.99999999999980993,
           676.5203681218851,
         -1259.1392167224028,
           771.32342877765313,
          -176.61502916214059,
            12.507343278686905,
            -0.13857109526572012,
             9.9843695780195716e-6,
             1.5056327351493116e-7,
        ];

        if ($z < 0.5) {
            // Reflexão: lnΓ(z) = ln(π) - ln(sin(πz)) - lnΓ(1-z)
            return log(M_PI) - log(abs(sin(M_PI * $z))) - $this->lnGamma(1.0 - $z);
        }

        $z -= 1.0;
        $x  = $coef[0];
        $g  = 7;

        for ($i = 1; $i < $g + 2; $i++) {
            $x += $coef[$i] / ($z + $i);
        }

        $t = $z + $g + 0.5;

        return 0.5 * log(2 * M_PI)
             + ($z + 0.5) * log($t)
             - $t
             + log($x);
    }

    // ── Helpers semânticos ────────────────────────────────────────────────────

    private function determinarDirecao(float $pearson): string
    {
        if ($pearson > 0.1)  return 'positiva';
        if ($pearson < -0.1) return 'negativa';
        return 'nao_linear';
    }

    private function tipoRelacao($varA, $varB): string
    {
        $eixoA = $varA->eixo?->slug ?? 'desconhecido';
        $eixoB = $varB->eixo?->slug ?? 'desconhecido';

        if ($eixoA === $eixoB) return "{$eixoA}→{$eixoB}";

        // Ordena alfabeticamente para garantir consistência
        $eixos = [$eixoA, $eixoB];
        sort($eixos);
        return implode('→', $eixos);
    }

    /**
     * Calcula correlações regionais (sem municipio_id) agrupando (pooling)
     * as observações temporais de todos os municípios de uma vez.
     * Isso aumenta o número de observações de 2 (por município) para 6 (3 municípios x 2 anos),
     * permitindo obter coeficientes e p-valores com validade matemática.
     */
    private function calcularCorrelacoesRegionais(
        array $pares,
        int $lagMax,
        float $pMax,
        bool $force,
        int &$salvos,
        int &$descartados
    ): void {
        $municipios = Municipio::all();
        $bar = $this->output->createProgressBar(count($pares) * ($lagMax + 1));
        $bar->start();

        foreach ($pares as [$varA, $varB]) {
            for ($lag = 0; $lag <= $lagMax; $lag++) {
                $bar->advance();

                $xSerie = [];
                $ySerie = [];

                foreach ($municipios as $m) {
                    // Busca todos os dados do cubo para este par de variáveis no município
                    $dadosA = DadoCubo::where('municipio_id', $m->id)
                        ->where('variavel_id', $varA->id)
                        ->get()
                        ->keyBy('ano_periodo');
                    
                    $dadosB = DadoCubo::where('municipio_id', $m->id)
                        ->where('variavel_id', $varB->id)
                        ->get()
                        ->keyBy('ano_periodo');

                    [$xSub, $ySub] = $this->alinharSeriesComLag($dadosA, $dadosB, $lag);
                    foreach ($xSub as $idx => $val) {
                        $xSerie[] = $val;
                        $ySerie[] = $ySub[$idx];
                    }
                }

                if (count($xSerie) < 3) {
                    $descartados++;
                    continue;
                }

                // Calcula correlação sobre o pool regional de dados
                $pearson  = $this->pearson($xSerie, $ySerie);
                $spearman = $this->spearman($xSerie, $ySerie);
                $pValor   = $this->pValorAproximado($pearson, count($xSerie));
                $n        = count($xSerie);

                if ($pValor > $pMax) {
                    $descartados++;
                    continue;
                }

                $chave = [
                    'variavel_a_id' => $varA->id,
                    'variavel_b_id' => $varB->id,
                    'municipio_id'  => null,
                    'lag_anos'      => $lag,
                ];

                $anos = DadoCubo::distinct()->pluck('ano_periodo')->sort();
                $periodoInicio = $anos->first() ?? '2000';
                $periodoFim    = $anos->last() ?? '2017';

                if ($force || !CorrelacaoVariavel::where($chave)->exists()) {
                    CorrelacaoVariavel::updateOrCreate($chave, array_merge($chave, [
                        'periodo_inicio' => $periodoInicio,
                        'periodo_fim'    => $periodoFim,
                        'coef_pearson'  => round($pearson, 6),
                        'coef_spearman' => round($spearman, 6),
                        'p_valor'       => round($pValor, 6),
                        'n_amostras'    => $n,
                        'direcao'       => $this->determinarDirecao($pearson),
                        'peso_grafo'    => round(abs($pearson) * (1 - $pValor), 6),
                        'tipo_relacao'  => $this->tipoRelacao($varA, $varB),
                        'metadados'     => [
                            'agregacao' => 'pool_dados_municipios',
                            'calculado_em' => now()->toISOString()
                        ],
                    ]));
                    $salvos++;
                }
            }
        }

        $bar->finish();
        $this->newLine();
    }
}
