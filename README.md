# All-in-one Laravel image processing

This package includes the following:
* images Eloquent model and migration;
* controller for uploads and on-the-fly image processing and caching.

Any uploaded or generated image is automatically optimized using the
[approached/laravel-image-optimizer](https://github.com/approached/laravel-image-optimizer) package.

On-the-fly image generation uses [intervention/image](http://image.intervention.io/) package.

## Installation

```
sudo apt-get install pngquant gifsicle jpegoptim
composer require mr-timofey/laravel-aio-images
php artisan vendor:publish --tag=imageoptimizer
php artisan vendor:publish --tag=aio_images
```

Add `Approached\LaravelImageOptimizer\ServiceProvider` and `MrTimofey\LaravelAioImages\ServiceProvider`
to your `app.providers` config.

```
php artisan migrate
```

See `config/aio_images.php` file for a further configuration instructions.

Do not forget to configure `aio_images.pipes`!

## Usage

Use `aio_images.upload` route to post new images.
Both multiple and single image uploads are supported.

Usage examples:

```php

// add relation to a table
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


// display avatar
echo '<img src="' . config('aio_image.public_path') . '/' .
	$model->avatar_image_id . '" alt="Original avatar" />';
echo '<img src="' . config('aio_image.public_path') . '/resize/' .
	$model->avatar_image_id . '" alt="Resized with resize pipe" />';


// display any image from ImageModel object
echo '<img src="' . $image->getPath() . '" alt="Original image" />';
echo '<img src="' . $image->getPath('resize') . '" alt="Resized with resize pipe" />';


// upload image manually

use Illuminate\Http\Request;
use MrTimofey\LaravelAioImages\ImageModel;

function upload(Request $req)
{
	return ImageModel::upload($req->file('image'));
}

```
