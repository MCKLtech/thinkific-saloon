<?php

namespace WooNinja\ThinkificSaloon\DataTransferObjects\Courses;

use WooNinja\LMSContracts\Contracts\DTOs\Courses\CourseInterface;

final class Course implements CourseInterface
{
    public function __construct(
        public int         $id,
        public string      $name,
        public string      $slug,
        public string|null $subtitle,
        public int         $product_id,
        public ?string     $description,
        public ?string     $course_card_text,
        public ?string     $intro_video_youtube,
        public ?string     $contact_information,
        public ?string     $keywords,
        public ?int        $duration,
        public string      $banner_image_url,
        public string      $course_card_image_url,
        public ?string     $intro_video_wistia_identifier,
        public array       $administrator_user_ids,
        public array       $chapter_ids,
        public bool        $reviews_enabled,
        public ?int         $user_id,
        public ?int         $instructor_id,

    )
    {

    }

}
