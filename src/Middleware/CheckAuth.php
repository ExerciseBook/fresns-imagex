<?php

namespace Plugins\ImageX\Middleware;

use App\Fresns\Api\Exceptions\ResponseException;
use App\Helpers\CacheHelper;
use App\Models\File;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Log;

class CheckAuth
{
    public function handle(Request $request, Closure $next)
    {
        $authUlid = Cookie::get('fresns_plugin_imagex_auth_ulid');
        if (empty($authUlid)) {
            throw new ResponseException(30001);
        }

        $cacheAuthUlid = CacheHelper::get($authUlid, 'fresnsPluginAuth');
        if (empty($cacheAuthUlid)) {
            throw new ResponseException(32203);
        }

        $usageType = Cookie::get('fresns_plugin_imagex_file_usage_type');
        $usageFsid = Cookie::get('fresns_plugin_imagex_file_usage_fsid');
        $fileType = Cookie::get('fresns_plugin_imagex_file_type');
        if (empty($usageType) || empty($usageFsid) || empty($fileType)) {
            throw new ResponseException(30001);
        }

        $langTag = Cookie::get('fresns_plugin_imagex_lang_tag');
        $authUid = Cookie::get('fresns_plugin_imagex_auth_uid');

        $request->headers->set('X-Fresns-Client-Lang-Tag', $langTag);
        $request->headers->set('X-Fresns-Uid', $authUid);

        // check upload perm
        $type = match ($fileType) {
            'image' => File::TYPE_IMAGE,
            'video' => File::TYPE_VIDEO,
            'audio' => File::TYPE_AUDIO,
            'document' => File::TYPE_DOCUMENT,
            default => null,
        };
        $wordBody = [
            'uid' => $authUid,
            'usageType' => $usageType,
            'usageFsid' => $usageFsid,
            'type' => $type,
            'extension' => $request->extension,
            'size' => $request->size,
            'duration' => $request->duration,
        ];
        $permResp = \FresnsCmdWord::plugin('Fresns')->checkUploadPerm($wordBody);

        if ($permResp->isErrorResponse()) {
            throw new ResponseException($permResp->getCode());
        }

        // request attributes
        $request->attributes->add($permResp->getData());

        return $next($request);
    }
}
