<?php

return [
    /**
     * Used as a prefix to request images via HTTP (relative public path).
     */
    'public_path' => '/storage/images',

    /**
     * Absolute path where all images will be stored.
     * IMPORTANT: public_path is used to access this folder so it should be
     *      a real physical path accessible by HTTP using public_path prefix.
     */
    'upload_path' => public_path('storage/images'),

    /**
     * On-the-fly image generation middleware.
     */
    'pipe_middleware' => [],

    /**
     * Images upload route. Set to false to disable.
     */
    'upload_route' => '/upload-image',

    /**
     * Images upload middleware.
     */
    'upload_middleware' => [],

    /**
     * Image generation pipes.
     * @see http://image.intervention.io/
     */
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
