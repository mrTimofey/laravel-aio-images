<?php /** @noinspection PhpUnused */

namespace MrTimofey\LaravelAioImages;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChain as ImageOptimizer;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use function call_user_func_array;
use function is_array;

class ImageController
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

    /**
     * @return JsonResponse|RedirectResponse
     * @throws Throwable
     */
    public function upload()
    {
        $uploaded = [];
        foreach ($this->req->allFiles() as $file) {
            if (is_array($file)) {
                foreach ($file as $_file) {
                    $uploaded[] = ImageModel::upload($_file)->getKey();
                }
            } else {
                $uploaded[] = ImageModel::upload($file)->getKey();
            }
        }
        return $this->req->wantsJson() ? response()->json($uploaded) : redirect()->back();
    }

    public function original(): void
    {
        throw new NotFoundHttpException('Use web server configuration to request original/generated images');
    }

    /**
     * @param string $pipeName
     * @param string $imageId
     * @return mixed
     * @throws Throwable
     */
    public function pipe(string $pipeName, string $imageId)
    {
        if (empty($this->pipesConfig[$pipeName]) || Str::endsWith($imageId, '.svg')) {
            throw new NotFoundHttpException();
        }
        $pipe = $this->pipesConfig[$pipeName];

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
            call_user_func_array([$intervention, array_shift($args)], $args);
        }
        $target = $img->getAbsPath($pipeName);

        // save and optimize
        $intervention->save($target);
        $this->optimizer->optimize($target);
        @chmod($target, 0664);

        // return file response
        return $intervention->response();
    }
}
