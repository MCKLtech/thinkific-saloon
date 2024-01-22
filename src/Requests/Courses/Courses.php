<?php

namespace WooNinja\ThinkificSaloon\Requests\Courses;

use Saloon\Enums\Method;
use Saloon\Http\Request;
use Saloon\Http\Response;
use Saloon\PaginationPlugin\Contracts\Paginatable;
use WooNinja\ThinkificSaloon\DataTransferObjects\Courses\Course;

final class Courses extends Request implements Paginatable
{
    protected Method $method = Method::GET;

    public function resolveEndpoint(): string
    {
        return "courses";
    }

    public function createDtoFromResponse(Response $response): array
    {
        return array_map(function (array $course) {
            return new Course(
                id: $course['id'],
                name: $course['name'],
                slug: $course['slug'],
                subtitle: $course['subtitle'],
                product_id: $course['product_id'],
                description: $course['description'],
                course_card_text: $course['course_card_text'],
                intro_video_youtube: $course['intro_video_youtube'],
                contact_information: $course['contact_information'],
                keywords: $course['keywords'],
                duration: $course['duration'],
                banner_image_url: $course['banner_image_url'],
                course_card_image_url: $course['course_card_image_url'],
                intro_video_wistia_identifier: $course['intro_video_wistia_identifier'],
                administrator_user_ids: $course['administrator_user_ids'],
                chapter_ids: $course['chapter_ids'],
                reviews_enabled: $course['reviews_enabled'],
                user_id: $course['user_id'],
                instructor_id: $course['instructor_id']
            );
        }, $response->json('items'));
    }
}