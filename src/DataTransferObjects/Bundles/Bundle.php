<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Bundles;

use WooNinja\LMSContracts\Contracts\DTOs\Bundles\BundleInterface;

final class Bundle implements BundleInterface
{

    /**
     * @param int $id
     * @param string $name
     * @param string|null $description
     * @param string $banner_image_url
     * @param array $course_ids
     * @param string $bundle_card_image_url
     * @param string|null $tagline
     * @param string $slug Always empty - Thinkific's Bundle API response has no slug field at all
     *                      (confirmed against the published OpenAPI schema). Kept as a non-nullable
     *                      string rather than widened to satisfy BundleInterface's signature.
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/getBundleByID
     */
    public function __construct(
        public int|string  $id,
        public string      $name,
        public string|null $description,
        public string      $banner_image_url,
        public array       $course_ids,
        public string      $bundle_card_image_url,
        public string|null $tagline,
        public string      $slug,
    )
    {
    }

}