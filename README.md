# thinkific-saloon

A PHP SDK for the Thinkific API implemented using Saloon

## Why Saloon?

I wrote my original PHP library for Thinkific in ~2019 and since then there have been some major improvements in both
PHP and libraries such as Saloon. With that in mind, I wanted to make the library more robust, strongly typed and easier
to maintain. In addition, Saloon has inbuilt support for pagination and rate limiting, lowering technical overhead when
dealing with high traffic integration scenarios.

## REST vs GraphQL

At time of writing (Jan 2024), Thinkific is slowly introducing a GraphQL API. This GraphQL API does not currently
replicate the complete functionality of the REST API. The intention of this library is to abstract the underlying API
call, meaning as and when GraphQL matures, end points will be updated to make use them where applicable with the
confidence that the interface to PHP will remain the same.

## Installation

* This library requires PHP 8.0 and later
* The recommended way to install is using Composer
* This library is intended to speed up development time but is not a shortcut to reading the Thinkific
  documentation. Many endpoints require specific and required fields for successful operation. Always read the
  documentation before using an endpoint.

```sh
composer require mckltech/thinkific-saloon
```

## Data Transfer Objects (DTOs)

The library makes extensive use of DTOs for entities such a Users, Courses, Products and Enrollments. Most endpoints
return a DTO or a collection of DTOs. The DTOs are strongly typed and will throw exceptions if required fields are not
present, in addition to the request failing if no DTO can be created.

When creating an entity, such as a User, or updating one, for example updating an enrollment, you will be required to
pass a DTO. Again, these are strongly typed and will throw exceptions if required fields are not present.

## Client - API Key

Initialize your client using your access token:

```php
$client = new \WooNinja\ThinkificSaloon\Services\ThinkificService(
  "XXXX428d55aabXXXXX68c0fXXXX",
  "example-school-123"
);
```

> - You can find your API Key by following the Thinkific API
    documentation: https://developers.thinkific.com/api/api-key-auth
> - For your subdomain, do not include .thinkific.com. For example, if your subdomain is example.thinkific.com, then you
    would use 'example' in your ThinkificClient set up. If you are using a custom domain, you should retrieve your
    Thinkific sub-domain from your Thinkific dashboard.

## Client - OAuth

The library permits the use of OAuth Access Tokens for API access, in addition to containing a helper method for
refreshing. Note, the library does not implement the OAuth flow itself. I recommend using a standalone library for this
e.g. Laravel Socialite

```php
$client = new \WooNinja\ThinkificSaloon\Services\ThinkificService(
  "API Key OR OAuth Access Token",
  "example-school-123",
  true
);
```


## Client - GraphQL

The library has basic support for GraphQL endpoints. Feel free to open a PR to add more, or request them via issues.

Below is a theoretical example for interacting with the GraphQL API. Note carefully the ThinkificGraphQLService class and the use of an OAuth/private token. You cannot use an API Key with the GraphQL API.

```php
use WooNinja\ThinkificSaloon\GraphQL\Services\ThinkificGraphQLService;

$client = new ThinkificGraphQLService(
  "eyJrg0ZmY1O...."
);

/**
* Fetch all users on Site
*/
$users = $client->users->users();

foreach ($users->items() as $user) {

    /**
    * Fetch all groups for a user (via email)
    */
    $groups = $client->users->groups($user->email);

    /**
    * Fetch all groups for user (via GID)
    */
    $groups = $client->users->groups($user->gid);

    /**
    * Fetch the User by Email
    */
    $theUser = $client->users->getByEmail($user->email);
}
```

## Support, Issues & Bugs

This library is unofficial and is not endorsed or supported by Thinkific.

For bugs and issues, open an issue in this repo and feel free to submit a PR. Any issues that do not contain full logs
or explanations will be closed. We need you to help us help you!

## Example Operations

```php
use WooNinja\ThinkificSaloon\DataTransferObjects\Users\CreateUser;

$client = new \WooNinja\ThinkificSaloon\Services\ThinkificService(
  "XXXX",
  "subdomain"
);

/* Fetch all Users */
$users = $client->users->users();

/* Paginate through all users */
foreach ($users->items() as $user) {
  /* Fetch a single user */ 
  $fetchedUser = $client->users->get($user->id);

}

/* Create a new User */
$newUser = new CreateUser(
  first_name: "John",
  last_name: "Doe",
  email: "john.doe@example.com",
  password: null,
  skip_custom_fields_validation: true,
  send_welcome_email: true,
  custom_profile_fields: null,
  roles: null,
  bio: "An example bio for the new student",
  company: "WooNinja Software",
  headline: null,
  affiliate_code: null,
  affiliate_commission: null,
  affiliate_commission_type: null,
  affiliate_payout_email: null,
  external_id: null,
  provider: null
);

$newUser = $client->users->create($newUser);

```

## Pagination

In general, the syntax for pagination is as follows:

```php
$users = $client->users->users();

foreach ($users->items() as $user) {
    // Do something with the user
}
```

To apply filters, supply them as an array. The following example, we are asking the API to return 2 results (Users) per page of results. We will start on Page 3 of the results, and we will iterate over a maximum of 4 pages. This will return 8 (2 Users x 4 Pages) results in total. It is recommend to limit your max_pages and work in batches for large result sets, as otherwise the system will iterate over all pages until the rate limit is reached.
    
```php
$users = $client->users->users(['limit' => 2, 'max_pages' => 4, 'start_page' => 3]);

foreach ($users->items() as $user) {
    // Do something with the user
}
```


## Supported Endpoints

All endpoints follow a similar mechanism to the examples show above. Again, please ensure you read the Thinkific API
documentation prior to use as there are numerous required fields for most POST/PUT operations.

- Bundles
- Chapters
- Contents
- Coupons
- Courses
- Course Reviews
- Custom Profile Field Definitions
- Enrollments
- Groups
- Instructors
- Products
- Promotions
- Users
- Webhooks
- OAuth Helper (Refresh Token Only)
  ID Conventions in the Thinkific API

## Linking Products, Courses and Enrollments

The Thinkific REST API uses a number of different IDs to link Courses, Products and Enrollments. Products, at time of
writing, can either be Courses or Bundles.

Given an **Enrollment**:

````
{
            "id": 479110111,
            "created_at": "2024-02-22T20:32:52.726Z",
            "user_email": "noreply@example.com",
            "user_name": "Colin",
            "expiry_date": "2025-01-17T20:32:52.000Z",
            "user_id": 193952525,
            "course_name": "Corporate Course",
            "course_id": 1264768, <-- The productable_id in /products and id in /courses
            "percentage_completed": "0.0",
            "completed_at": null,
            "expired": false,
            "is_free_trial": false,
            "completed": false,
            "started_at": null,
            "activated_at": "2024-02-22T20:32:52.000Z",
            "updated_at": "2024-02-22T20:32:52.730Z"
        }
````

You can relate this **Enrollment** to a **Product**:

````
{
            "id": 1325780, <-- The product_id in /courses
            "created_at": "2021-03-04T21:30:50.243Z",
            "productable_id": 1264768 <- The course_id in /enrollments & the id in /courses
            "productable_type": "Course",
            "price": "1.0",
            "position": 13,
            "status": "published",
            "name": "Corporate Course",
            "private": false,
            "hidden": false,
            "subscription": false,
            "days_until_expiry": null,
            "has_certificate": false,
            "collection_ids": [],
            "seo_title": null,
            "seo_description": null,
            "keywords": null,
            "related_product_ids": [],
            "slug": "your-first-course",
            "description": "Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. Unam incolunt Belgae, aliam Aquitani, tertiam. Cras mattis iudicium purus sit amet fermentum.",
            ...
        }
````

Which, in turn, can be related to a **Course**:

````
{
            "id": 1264768, <-- The productable_id in /products and course_id in /enrollments
            "name": "Corporate Course",
            "slug": "your-first-course",
            "subtitle": null,
            "product_id": 1325780, <- The id in /products
            "description": "Lorem ipsum dolor sit amet, consectetur adipisici elit, sed eiusmod tempor incidunt ut labore et dolore magna aliqua. Unam incolunt Belgae, aliam Aquitani, tertiam. Cras mattis iudicium purus sit amet fermentum.",
            "intro_video_youtube": null,
            "contact_information": null,
            "keywords": null,
            "duration": null,
            ...
        }
````

For a **Bundle**, they can be listed via an API request to:

````
e.g. https://api.thinkific.com/api/public/v1/bundles/120008
````

Where '120008' is the **productable_id** from a **Product**

Which returns:

````
{
    "id": 120008, <-- The productable_id in /products
    "name": "Demo Bundle",
    "description": null,
    "banner_image_url": "/assets/tenant/default-course-banner.jpg",
    "course_ids": [
        1264768, <-- The productable_id in /products and id in /courses
    ],
    "bundle_card_image_url": "/assets/defaults/default-product-card.png",
    "tagline": null,
    "slug": "demo-bundle"
}
````

When creating and updating enrollments, the **productable_id** from a **Product** should be used.

As a worked example of iteration, we can query the API for all products, and then determine the Course(s) associated with each Product:

````php

$products = $client->products->products();

foreach ($products->items() as $product) {

    /**
     * Not required to if/else here but we'll use it to highlight the alternative method
     * of retrieving the course associated with a product
     */
    if ($product->productable_type == "Course") {
        
        /**
         * We could also query the course directly:
         *  
         * Note the use of 'productable_id' when querying the course end point
         */
        
        //$courses = $client->courses->get($product->productable_id);

        /**
         * And here we will use the products service to retrieve the course
         * Note the use of the Product 'ID'
         */
        $courses = $client->products->courses($product->id);
        
    } else {
        /**
         * Bundles
         */
        $courses = $client->products->courses($product->id);
    }
    
    /**
     * $courses is an array of Course objects
     */
}

````

## Additional Features

- Helper methods e.g. find() and findByEmail() in User endpoints
- WordPressRateLimitStore for Rate Limiting in WordPress Environments (Uses WP Transients)
- MapperTrait to transfer between DTOs e.g. CreateUser -> UpdateUser

## Exceptions

Exceptions are handled by Saloon. Most end points for retrieving data will either return a DTO (or collection of DTOs)
or fail. Further docs here: https://docs.saloon.dev/the-basics/handling-failures

## Credit

The layout and methodology used in this library was inspired by Ash Allen from https://battle-ready-laravel.com/


