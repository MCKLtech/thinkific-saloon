<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Bundles;

final class Bundle
{

    /**
     * @param int $id
     * @param string $name
     * @param string|null $description
     * @param string $banner_image_url
     * @param array $course_ids
     * @param string $bundle_card_image_url
     * @param string|null $tagline
     * @param string $slug
     * @see https://developers.thinkific.com/api/api-documentation/#/Bundles/getBundleByID
     */
    public function __construct(
        public int         $id,
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