<?php

namespace MrTimofey\LaravelAioImages;

use Approached\LaravelImageOptimizer\ImageOptimizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Image;

/**
 * @property array $props
 */
class ImageModel extends Model
{
    public $timestamps = false;
    public $incrementing = false;

    protected $table = 'aio_images';

    protected $casts = [
        'props' => 'array'
    ];

    protected $visible = [
        'id',
        'props',
        'created_at'
    ];

    protected $fillable = [
        'id',
        'props'
    ];

    protected $dates = [
        'created_at'
    ];

    public static function getUploadPath()
    {
        return rtrim(config('aio_images.upload_path'), '/');
    }

    protected function unlink()
    {
        $fs = app('files');
        $fs->delete($this->getAbsPath());
        if (!empty($this->props['pipes'])) {
            foreach ((array)$this->props['pipes'] as $pipe) {
                $fs->delete($this->getAbsPath($pipe));
            }
        }
    }

    /**
     * Create and save image from uploaded file or Intervention image object. Optimize image after saving.
     * @param UploadedFile|Image $file
     * @param array $props additional props
     * @return self
     * @throws \Throwable
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     * @throws \InvalidArgumentException
     * @throws \Exception
     */
    public static function upload($file, array $props = [])
    {
        if (isset($props['ext'])) {
            $ext = $props['ext'];
            unset($props['ext']);
        } else {
            $ext = null;
        }

        if (!empty($props['name'])) {
            $name = $props['name'];
            if ($ext) {
                $props['name'] .= '.' . $ext;
            }
        } else {
            $name = str_random(6) . time();
        }

        if (!$ext && $name) {
            $pieces = explode('.', $name);
            if (\count($pieces) > 1) {
                $ext = array_pop($pieces);
                $name = implode('.', $pieces);
            }
        }

        $uploadPath = static::getUploadPath();

        if ($file instanceof InterventionImage) {
            if (!$ext) {
                throw new \InvalidArgumentException('Can not guess file extension from the Intervention\Image\Image instance');
            }
            $fileName = $name . '.' . $ext;
            $file->save($uploadPath . $fileName);
        } elseif ($file instanceof UploadedFile) {
            $mime = $file->getMimeType();

            // fix SVG mime bug
            if ($mime === 'text/html' && $file->getClientOriginalExtension() === 'svg') {
                $mime = 'image/svg+xml';
            }
            if (!starts_with($mime, 'image')) {
                throw new \InvalidArgumentException('Illuminate\Http\UploadedFile is not an image');
            }
            if (!$ext) {
                $ext = !empty($file->getClientOriginalExtension()) ? $file->getClientOriginalExtension() : $file->guessExtension();
            }
            $fileName = $name . '.' . $ext;
            $file->move($uploadPath, $fileName);
            if (empty($props['name'])) {
                $props['name'] = $file->getClientOriginalName();
            }
        } else {
            throw new \InvalidArgumentException('Wrong argument type: first argument can be ' .
                'Intervention\Image\Image or Illuminate\Http\UploadedFile instance');
        }

        if ($ext !== 'svg') {
            app(ImageOptimizer::class)->optimizeImage($uploadPath . '/' . $fileName);
        }
        @chmod($uploadPath . $fileName, 0664);
        $i = new static();
        foreach ($props as $k => $v) {
            if (!$v) {
                unset($props[$k]);
            }
        }
        $i->attributes['id'] = $fileName;
        $i->props = $props;
        $i->saveOrFail();
        return $i;
    }

    /**
     * @param string|null $pipeName
     * @return string
     */
    public function getPath($pipeName = null)
    {
        return $pipeName ? route('aio_images.pipe', [$pipeName, $this->attributes['id']]) :
            route('aio_images.original', $this->attributes['id']);
    }

    /**
     * @param string|null $pipeName
     * @return string
     */
    public function getAbsPath($pipeName = null)
    {
        $uploadPath = static::getUploadPath();
        if ($pipeName) {
            $uploadPath .= '/' . $pipeName;
            $fs = app('files');
            if (!$fs->exists($uploadPath)) {
                $fs->makeDirectory($uploadPath);
            }
        }
        return $uploadPath . '/' . $this->attributes['id'];
    }

    public function setIdAttribute(string $value)
    {
        if (empty($this->attributes['id'])) {
            $this->attributes['id'] = $value;
            return;
        }
        throw new \InvalidArgumentException('You can not set image ID manually!');
    }

    public static function boot()
    {
        parent::boot();
        static::deleting(function (self $i) {
            $i->unlink();
        });
    }
}
