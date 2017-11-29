<?php

namespace MrTimofey\LaravelAioImages;

use Approached\LaravelImageOptimizer\ImageOptimizer;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Intervention\Image\ImageManager;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class ImageController extends Controller
{
    /**
     * @var Request
     */
    protected $req;

    /**
     * @var ImageManager
     */
    protected $manager;

    /**
     * @var ImageOptimizer
     */
    protected $optimizer;

    /**
     * @var array
     */
    protected $pipesConfig;

    public function __construct(Request $req, ImageManager $manager, ImageOptimizer $optimizer)
    {
        $this->req = $req;
        $this->manager = $manager;
        $this->optimizer = $optimizer;
        $this->pipesConfig = config('aio_images.pipes');
    }

    public function upload()
    {
        $uploaded = [];
        foreach ($this->req->allFiles() as $file) {
            $uploaded[] = ImageModel::upload($file);
        }
        return $this->req->wantsJson() ? response()->json($uploaded) : redirect()->back();
    }

    public function original()
    {
        throw new \RuntimeException('Use web server configuration to request original/generated images');
    }

    public function pipe(string $pipeName, string $imageId)
    {
        if (empty($this->pipesConfig[$pipeName]) || ends_with($imageId, '.svg')) {
            throw new NotFoundHttpException();
        }
        $pipe = $this->pipesConfig[$pipeName]['pipe'];

        /** @var ImageModel $img */
        $img = ImageModel::query()->findOrFail($imageId);
        $props = $img->props;
        if (!isset($props['pipes'])) {
            $props['pipes'] = [];
        }

        // append pipe
        $props['pipes'][] = $pipeName;
        $props['pipes'] = array_unique($props['pipes']);
        $img->props = $props;
        $img->saveOrFail();

        // go through pipe
        $intervention = $this->manager->make($img->getAbsPath());
        foreach ((array)$pipe as $args) {
            \call_user_func_array([$intervention, array_shift($args)], $args);
        }
        $target = $img->getAbsPath($pipeName);

        // save and optimize
        $intervention->save($target);
        $this->optimizer->optimizeImage($target);
        @chmod($target, 0664);

        // return file response
        return $intervention->response();
    }
}