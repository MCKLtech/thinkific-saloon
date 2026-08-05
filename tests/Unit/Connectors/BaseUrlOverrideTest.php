<?php

namespace WooNinja\ThinkificSaloon\Tests\Unit\Connectors;

use ReflectionClass;
use Saloon\Http\Request;
use Saloon\Http\SoloRequest;
use WooNinja\ThinkificSaloon\Requests\Users\Get;
use WooNinja\ThinkificSaloon\Tests\TestCase;

/**
 * Saloon v4 (fixing CVE-2026-33182) disables base URL overriding by default:
 * a Request's resolveEndpoint() can no longer silently replace the
 * Connector's base URL unless $allowBaseUrlOverride is explicitly opted in.
 * These tests pin that behaviour for this SDK.
 */
class BaseUrlOverrideTest extends TestCase
{
    public function test_request_is_resolved_against_the_connector_base_url(): void
    {
        $connector = $this->service->connector();

        $pendingRequest = $connector->createPendingRequest(new Get(123));

        $this->assertSame(
            'https://api.thinkific.com/api/public/v1/users/123',
            $pendingRequest->getUrl()
        );
    }

    /**
     * Guards against a future request class re-enabling the base URL
     * override behaviour that Saloon v4 disabled by default for security
     * reasons, without an explicit and deliberate opt-in.
     */
    public function test_no_request_class_opts_into_base_url_override(): void
    {
        foreach ($this->allRequestClasses() as $class) {
            // SoloRequest has no Connector base URL to protect, so Saloon
            // itself defaults $allowBaseUrlOverride to true for it - that's
            // expected, not an opt-in worth guarding against here.
            if (is_a($class, SoloRequest::class, true)) {
                continue;
            }

            $request = new ReflectionClass($class);

            $this->assertNull(
                $request->getDefaultProperties()['allowBaseUrlOverride'] ?? null,
                "{$class} should not opt into \$allowBaseUrlOverride unless deliberately required."
            );
        }
    }

    /**
     * @return array<class-string<Request>>
     */
    private function allRequestClasses(): array
    {
        $classes = [];

        foreach (['src/Requests', 'src/GraphQL/Requests'] as $dir) {
            $path = dirname(__DIR__, 3) . '/' . $dir;

            $files = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($files as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $relative = str_replace([$path . '/', '.php'], '', $file->getPathname());
                $namespace = str_starts_with($dir, 'src/GraphQL')
                    ? 'WooNinja\\ThinkificSaloon\\GraphQL\\Requests\\'
                    : 'WooNinja\\ThinkificSaloon\\Requests\\';

                $class = $namespace . str_replace('/', '\\', $relative);

                if (is_a($class, Request::class, true)) {
                    $classes[] = $class;
                }
            }
        }

        return $classes;
    }
}