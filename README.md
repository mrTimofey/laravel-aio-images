# All-in-one Laravel image processing

This package includes the following:
* images database table migration;
* images Eloquent model;
* controller for uploads and on-the-fly/on-demand image processing/caching;
* service provider.

Any uploaded or generated image is automatically optimized using the
[spatie/image-optimizer](https://github.com/spatie/image-optimizer) package.

On-the-fly image generation just uses [intervention/image](http://image.intervention.io/) package.

## Requirements

* PHP 7.1
* Laravel 5

## Installation

```bash
sudo apt-get install pngquant gifsicle jpegoptim
composer require mr-timofey/laravel-aio-images
```

**For Laravel <= 5.4**
add
`Approached\LaravelImageOptimizer\ServiceProvider`,
`Intervention\Image\ImageServiceProvider`,
`MrTimofey\LaravelAioImages\ServiceProvider`
to your `app.providers` config.

```bash
php artisan vendor:publish --provider="Intervention\Image\ImageServiceProviderLaravel5"
php artisan vendor:publish --provider="MrTimofey\LaravelAioImages\ServiceProvider"
php artisan migrate
```

**Migration will create the `aio_images` table which contains a `json` field `props`.
You can change it to `text` if your database does not support JSON/JSONB fields.
Although it is not recommended.**

If you want to use `storage/app/public` as a place to store all your images (configured by default):

```bash
php artisan storage:link
```

See `config/aio_images.php` file for a further configuration instructions.
**Do not forget to configure `aio_images.pipes`!**

## Predefined routes

* `route('aio_images.upload')`, `POST multipart/form-data` - image uploads handler.
	Both multiple and single image uploads are supported.
	Field names does not matter since the controller just uses `Illuminate\Http\Request@allFiles()` to get your uploads.
* `route('aio_images.original', $image_id)` - original image path.
* `route('aio_images.pipe', [$pipe, $image_id])` - processed image path.

## Usage example:

```php

// add relation to a table
/** @var Illuminate\Database\Schema\Blueprint $table */
$table->string('avatar_image_id')->nullable();
$table->foreign('avatar_image_id')
	->references('id')
	->on('aio_images')
	->onDelete('set null');


// add relation to a model
public function avatarImage()
{
	$model->belongsTo(ImageModel::class, 'avatar_image_id');
}


// create pipe config in config/aio_images.php
[
	// ...
	'pipes' => [
		// /storage/images/avatar/image-id.jpg
		'avatar' => [
			// $interventionImage->fit(120)
			['fit', 120],
			// $interventionImage->greyscale()
			['greyscale']
		]
	]
];

// display original avatar
echo '<img src="' . route('aio_images.original', $model->avatar_image_id) . '" alt="Original avatar" />';
// display 120x120 squared grey colored avatar
echo '<img src="' . route('aio_images.pipe', ['avatar', $model->avatar_image_id]) . '" alt="Processed with pipe [avatar]" />';

// same with ImageModel instance
echo '<img src="' . $image->getPath() . '" alt="Original avatar" />';
echo '<img src="' . $image->getPath('avatar') . '" alt="Processed with pipe [avatar]" />';


// upload image manually from any of your custom controllers

use Illuminate\Http\Request;
use MrTimofey\LaravelAioImages\ImageModel;

function upload(Request $req)
{
	return ImageModel::upload($req->file('image'));
}

```
