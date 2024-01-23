<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts;

class CreateSiteScript
{
    public function __construct(
        public string  $name,
        public string  $description,
        public array   $page_scopes,
        public ?string $src,
        public ?string $content,
        public ?string $location,
        public ?string $load_method,
        public ?string $category,
    )
    {
    }

}