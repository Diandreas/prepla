<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

/**
 * Pré-génère une bibliothèque d'images via Pollinations.ai (gratuit, sans clé).
 * Les images sont stockées dans storage/app/public/exercise-images/{exam}/
 *
 * Usage:
 *   php artisan images:generate-library            # génère tout
 *   php artisan images:generate-library --exam=ielts
 *   php artisan images:generate-library --dry-run
 */
class GenerateImageLibraryCommand extends Command
{
    protected $signature = 'images:generate-library
                            {--exam= : Exam slug to generate for (ielts, torfl, celpe)}
                            {--dry-run : Show prompts without calling API}
                            {--force : Re-generate even if image already exists}';

    protected $description = 'Pre-generate exercise image library via Pollinations.ai';

    private const BASE_URL = 'https://image.pollinations.ai/prompt/';
    private const DISK = 'public';
    private const WIDTH = 900;
    private const HEIGHT = 600;

    private function getLibrary(): array
    {
        return [
            'ielts' => [
                // Graphs & Charts (Academic Writing Task 1)
                'bar-chart-co2-emissions' => [
                    'prompt' => 'clean academic bar chart showing CO2 emissions by country 2000 to 2020, white background, labeled axes, data visualization, no text overlays',
                    'label'  => 'Bar chart: CO2 emissions by country',
                ],
                'line-graph-internet-users' => [
                    'prompt' => 'clean academic line graph showing internet users growth in 5 countries from 1995 to 2020, white background, labeled axes, multiple colored lines, legend',
                    'label'  => 'Line graph: Internet users growth',
                ],
                'pie-chart-energy-sources' => [
                    'prompt' => 'clean academic pie chart showing energy sources percentages: coal 35%, natural gas 25%, renewables 20%, nuclear 12%, other 8%, white background, labeled segments',
                    'label'  => 'Pie chart: Energy sources',
                ],
                'bar-chart-unemployment' => [
                    'prompt' => 'clean academic grouped bar chart comparing unemployment rates in 4 countries across 3 decades, white background, labeled axes, legend',
                    'label'  => 'Bar chart: Unemployment rates comparison',
                ],
                'line-graph-temperature' => [
                    'prompt' => 'clean academic line graph showing average monthly temperatures for two cities over one year, white background, two colored lines, labeled axes',
                    'label'  => 'Line graph: Monthly temperatures',
                ],
                'table-tourism-data' => [
                    'prompt' => 'clean academic data table showing international tourist arrivals by region 2015 to 2022, white background, neat grid layout, numbers in millions',
                    'label'  => 'Table: International tourism data',
                ],
                'bar-chart-spending' => [
                    'prompt' => 'clean academic horizontal bar chart comparing household spending categories between two countries, white background, labeled bars, percentage values',
                    'label'  => 'Bar chart: Household spending',
                ],
                'mixed-graph-production' => [
                    'prompt' => 'clean academic combination chart with bar and line graph showing production volume and profit margin over 10 years, white background, dual axes',
                    'label'  => 'Mixed chart: Production and profit',
                ],
                'pie-chart-age-groups' => [
                    'prompt' => 'clean academic two pie charts side by side comparing age group distribution of a city population in 1980 and 2020, white background, labeled segments',
                    'label'  => 'Pie charts: Age group comparison',
                ],
                'line-graph-water-usage' => [
                    'prompt' => 'clean academic line graph showing water consumption in agriculture industry and domestic use from 1960 to 2010, white background, three colored lines',
                    'label'  => 'Line graph: Water usage by sector',
                ],

                // Process Diagrams
                'process-water-cycle' => [
                    'prompt' => 'clean scientific diagram of the water cycle showing evaporation condensation precipitation and groundwater flow, white background, labeled arrows, educational style',
                    'label'  => 'Process diagram: The water cycle',
                ],
                'process-recycling-paper' => [
                    'prompt' => 'clean flowchart diagram showing the paper recycling process from collection to finished product, white background, numbered steps, arrows connecting boxes',
                    'label'  => 'Process diagram: Paper recycling',
                ],
                'process-solar-panel' => [
                    'prompt' => 'clean technical diagram showing how a solar panel system generates and distributes electricity, white background, labeled components, arrows showing energy flow',
                    'label'  => 'Process diagram: Solar panel electricity',
                ],
                'process-cement-production' => [
                    'prompt' => 'clean industrial process diagram showing cement production from limestone quarrying to final product, white background, sequential steps with arrows',
                    'label'  => 'Process diagram: Cement production',
                ],
                'process-salmon-lifecycle' => [
                    'prompt' => 'clean scientific life cycle diagram of a salmon from egg to adult and spawning, white background, circular flow with labeled stages',
                    'label'  => 'Process diagram: Salmon life cycle',
                ],
                'process-brick-manufacturing' => [
                    'prompt' => 'clean industrial flowchart of brick manufacturing process from clay digging to finished brick, white background, linear steps with machines labeled',
                    'label'  => 'Process diagram: Brick manufacturing',
                ],
                'process-chocolate-production' => [
                    'prompt' => 'clean diagram showing chocolate production process from cocoa beans harvesting to packaged chocolate bars, white background, numbered sequential steps',
                    'label'  => 'Process diagram: Chocolate production',
                ],
                'process-wind-turbine' => [
                    'prompt' => 'clean technical cross-section diagram of a wind turbine showing internal components and how it generates electricity, white background, labeled parts',
                    'label'  => 'Process diagram: Wind turbine operation',
                ],
                'process-water-treatment' => [
                    'prompt' => 'clean engineering diagram of a water treatment plant showing filtration stages from intake to clean drinking water, white background, labeled tanks and processes',
                    'label'  => 'Process diagram: Water treatment plant',
                ],
                'process-food-web' => [
                    'prompt' => 'clean ecological diagram showing a food web in a forest ecosystem with producers consumers and decomposers, white background, arrows showing energy flow',
                    'label'  => 'Process diagram: Forest food web',
                ],

                // Maps & Floor Plans
                'map-town-development' => [
                    'prompt' => 'clean bird eye view town map showing urban development before and after, simple buildings roads park and river, white background, labeled streets, academic diagram style',
                    'label'  => 'Map: Town development before/after',
                ],
                'map-museum-floorplan' => [
                    'prompt' => 'clean architectural floor plan of a museum with galleries café gift shop entrance and toilets, white background, labeled rooms, top-down view',
                    'label'  => 'Map: Museum floor plan',
                ],
                'map-island-tourist' => [
                    'prompt' => 'clean simple map of a small tourist island showing beach hotel roads forest and marina, white background, compass rose, labeled locations',
                    'label'  => 'Map: Tourist island layout',
                ],
                'map-university-campus' => [
                    'prompt' => 'clean campus map of a small university with library lecture halls sports center cafeteria and dormitories, white background, labeled buildings, paths',
                    'label'  => 'Map: University campus',
                ],
                'map-shopping-centre' => [
                    'prompt' => 'clean floor plan of a shopping centre with shops restaurants cinema parking and entrances, white background, labeled areas, top-down architectural view',
                    'label'  => 'Map: Shopping centre floor plan',
                ],
                'map-city-transport' => [
                    'prompt' => 'clean city map showing public transport routes bus stops train stations and metro lines, white background, color coded lines, labeled stops',
                    'label'  => 'Map: City transport network',
                ],
                'map-nature-reserve' => [
                    'prompt' => 'clean map of a nature reserve with walking trails picnic areas bird watching spots visitor centre and car park, white background, labeled features',
                    'label'  => 'Map: Nature reserve',
                ],
                'map-library-layout' => [
                    'prompt' => 'clean floor plan of a public library with reading rooms computer section children area reception and study pods, white background, labeled sections',
                    'label'  => 'Map: Library floor plan',
                ],
                'map-sports-complex' => [
                    'prompt' => 'clean top-down plan of a sports complex with swimming pool gym tennis courts changing rooms and reception, white background, labeled areas',
                    'label'  => 'Map: Sports complex layout',
                ],
                'map-airport-terminal' => [
                    'prompt' => 'clean airport terminal floor plan with check-in gates departure lounge shops toilets and customs, white background, labeled zones, simple architectural style',
                    'label'  => 'Map: Airport terminal layout',
                ],
            ],
        ];
    }

    public function handle(): int
    {
        $library = $this->getLibrary();
        $examFilter = $this->option('exam');

        if ($examFilter && !isset($library[$examFilter])) {
            $this->error("Unknown exam: {$examFilter}. Available: " . implode(', ', array_keys($library)));
            return self::FAILURE;
        }

        $toGenerate = $examFilter ? [$examFilter => $library[$examFilter]] : $library;

        $totalImages = array_sum(array_map('count', $toGenerate));
        $this->info("Generating {$totalImages} image(s)...");

        $bar = $this->output->createProgressBar($totalImages);
        $bar->start();

        $generated = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($toGenerate as $exam => $images) {
            foreach ($images as $slug => $meta) {
                $bar->advance();
                $path = "exercise-images/{$exam}/{$slug}.jpg";

                if (!$this->option('force') && Storage::disk(self::DISK)->exists($path)) {
                    $skipped++;
                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->newLine();
                    $this->line("  [dry] {$exam}/{$slug}: {$meta['label']}");
                    continue;
                }

                $url = self::BASE_URL . urlencode($meta['prompt'])
                    . '?width=' . self::WIDTH
                    . '&height=' . self::HEIGHT
                    . '&model=sana'
                    . '&nologo=true'
                    . '&seed=' . crc32($slug);

                // Respect Pollinations.ai anonymous rate limit (1 req / 15s)
                sleep(18);

                try {
                    $response = Http::timeout(90)->get($url);

                    if ($response->successful() && strlen($response->body()) > 5000) {
                        Storage::disk(self::DISK)->put($path, $response->body());
                        $generated++;
                    } else {
                        $this->newLine();
                        $this->warn("  Failed [{$response->status()}]: {$slug}");
                        $failed++;
                    }
                } catch (\Exception $e) {
                    $this->newLine();
                    $this->warn("  Exception: {$slug} — {$e->getMessage()}");
                    $failed++;
                }
            }
        }

        $bar->finish();
        $this->newLine(2);
        $this->info("✅ Generated {$generated} image(s)");
        if ($skipped > 0) $this->warn("⏭  Skipped {$skipped} (already exist, use --force to re-generate)");
        if ($failed > 0)  $this->error("❌ Failed {$failed} image(s)");

        // Print public URLs
        if ($generated > 0) {
            $this->newLine();
            $this->info('Images available at: /storage/exercise-images/{exam}/{slug}.jpg');
        }

        return self::SUCCESS;
    }
}
