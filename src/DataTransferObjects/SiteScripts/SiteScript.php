<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\SiteScripts;

class SiteScript
{
    public function __construct(
        public string $id,
        public string $content,
        public string $name,
        public string $description,
        public array  $page_scopes,
        public string $location,
        public string $load_method,
        public string $category,
    )
    {
    }

}