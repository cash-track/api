<?php

declare(strict_types=1);

namespace Tests\Unit\Service\Metrics;

use App\Service\Metrics\AppMetrics;
use Symfony\Component\Yaml\Yaml;
use Tests\TestCase;

/**
 * Guards the static `metrics.collect:` block in `.rr.yaml` against drift from the code that
 * writes to those series. The collectors are declared plugin-side from that YAML now (not at
 * runtime), so a rename/label change on one side without the other would silently break the
 * metric — this test fails the build instead.
 */
final class MetricsCollectConfigTest extends TestCase
{
    /**
     * @return array<string, mixed>
     */
    private function collectConfig(): array
    {
        $path = dirname(__DIR__, 4) . '/.rr.yaml';
        $this->assertFileExists($path);

        /** @var array<string, mixed> $parsed */
        $parsed = Yaml::parseFile($path);

        $collect = $parsed['metrics']['collect'] ?? null;
        $this->assertIsArray($collect, '.rr.yaml is missing the metrics.collect block');

        return $collect;
    }

    public function testEveryDefinedMetricHasAMatchingCollectEntry(): void
    {
        $collect = $this->collectConfig();

        foreach (AppMetrics::definitions() as $name => $definition) {
            $this->assertArrayHasKey(
                $name,
                $collect,
                sprintf('metrics.collect in .rr.yaml has no entry for "%s"', $name),
            );

            $entry = $collect[$name];
            $this->assertIsArray($entry);

            $this->assertSame(
                $definition['type'],
                $entry['type'] ?? null,
                sprintf('metrics.collect["%s"].type disagrees with AppMetrics::definitions()', $name),
            );

            $this->assertSame(
                $definition['labels'],
                $entry['labels'] ?? [],
                sprintf('metrics.collect["%s"].labels disagrees with AppMetrics::definitions()', $name),
            );

            if (isset($definition['buckets'])) {
                $yamlBuckets = $entry['buckets'] ?? [];
                $this->assertIsArray($yamlBuckets);
                $this->assertCount(
                    count($definition['buckets']),
                    $yamlBuckets,
                    sprintf('metrics.collect["%s"].buckets length disagrees with AppMetrics::definitions()', $name),
                );

                foreach ($definition['buckets'] as $i => $bucket) {
                    $this->assertEqualsWithDelta(
                        $bucket,
                        (float) $yamlBuckets[$i],
                        1.0e-9,
                        sprintf('metrics.collect["%s"].buckets[%d] disagrees with AppMetrics::definitions()', $name, $i),
                    );
                }
            }

            // The `app_` prefix must live in the name only; namespace/subsystem here would
            // make BuildFQName emit a doubled `app_app_*` series.
            $this->assertArrayNotHasKey('namespace', $entry, sprintf('metrics.collect["%s"] must not set namespace', $name));
            $this->assertArrayNotHasKey('subsystem', $entry, sprintf('metrics.collect["%s"] must not set subsystem', $name));
        }
    }

    public function testCollectBlockHasNoUndeclaredAppMetrics(): void
    {
        $collect = $this->collectConfig();
        $known = array_keys(AppMetrics::definitions());

        foreach (array_keys($collect) as $name) {
            $this->assertMatchesRegularExpression(
                '/^app_[a-z0-9_]+$/',
                (string) $name,
                sprintf('metrics.collect key "%s" is not a lower_snake app_-prefixed name', (string) $name),
            );
            $this->assertContains(
                $name,
                $known,
                sprintf('metrics.collect has a stray "%s" entry with no AppMetrics::definitions() counterpart', (string) $name),
            );
        }
    }
}
