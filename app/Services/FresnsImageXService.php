<?php


namespace Plugins\ImageX\Services;

use App\Helpers\FileHelper;
use ExerciseBook\Flysystem\ImageX\ImageXConfig;
use Illuminate\Support\Arr;

class FresnsImageXService
{
    protected int $storageId = 21;

    protected string $defaultBucketName = "";

    protected array $userConfig = [];

    private ImageXConfig $imagexConfig;

    public function __construct(int $type)
    {
        $settings = FileHelper::fresnsFileStorageConfigByType($type);


        $config = new ImageXConfig();
        $config->region = Arr::get($settings, 'bucketArea', 'cn-north-1');
        if ($config->region == null || strlen($config->region) == 0) {
            $config->region = 'cn-north-1';
        }
        $config->accessKey = Arr::get($settings, 'secretId', '');
        $config->secretKey = Arr::get($settings, 'secretKey', '');
        $config->serviceId = Arr::get($settings, 'bucketName', '');
        $config->domain = Arr::get($settings, 'bucketDomain', '');

//        $this->imagePreviewTemplate = $this->read_template($settings->get('exercisebook-fof-upload-imagex.imagexConfig.imagePreviewTemplate', ''));
//        $this->imageFullscreenTemplate = $this->read_template($settings->get('exercisebook-fof-upload-imagex.imagexConfig.imageFullscreenTemplate', ''));
//        $this->fileRetrievingSignatureToken = $settings->get('exercisebook-fof-upload-imagex.imagexConfig.fileRetrievingSignatureToken', '');
        $this->imagexConfig = $config;
    }

}
