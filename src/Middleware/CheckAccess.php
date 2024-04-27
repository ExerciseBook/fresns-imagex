<?php

namespace Plugins\ImageX\Middleware;

use App\Helpers\AppHelper;
use App\Helpers\CacheHelper;
use App\Models\File;
use App\Utilities\ConfigUtility;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Str;

class CheckAccess
{
    public function handle(Request $request, Closure $next)
    {
        // verify access token
        $fresnsResp = \FresnsCmdWord::plugin('Fresns')->verifyAccessToken([
            'accessToken' => $request->accessToken,
            'userLogin' => true,
        ]);

        $langTag = $fresnsResp->getData('langTag') ?? AppHelper::getLangTag();
        View::share('langTag', $langTag);

        if ($fresnsResp->isErrorResponse()) {
            $code = $fresnsResp->getCode();
            $message = $fresnsResp->getMessage() . ' (accessToken)';

            return response()->view('ImageX::error', compact('code', 'message'), 403);
        }

        // postMessageKey
        $postMessageKey = $request->postMessageKey;
        View::share('postMessageKey', $postMessageKey);

        // postMessageKey
        $uploadInfo = $request->uploadInfo;
        if (empty($uploadInfo)) {
            $code = 30001;
            $message = ConfigUtility::getCodeMessage(30001, 'Fresns', $langTag) . ' (uploadInfo)';

            return response()->view('ImageX::error', compact('code', 'message'), 403);
        }

        // usageType,usageFsid,fileType,archiveCode
        $uploadInfoExplode = explode(',', $uploadInfo);
        $uploadInfoArr = [
            'usageType' => $uploadInfoExplode[0] ?? null,
            'usageFsid' => $uploadInfoExplode[1] ?? null,
            'fileType' => $uploadInfoExplode[2] ?? null,
            'archiveCode' => $uploadInfoExplode[3] ?? null,
        ];

        // check upload perm
        $type = match ($uploadInfoArr['fileType']) {
            'image' => File::TYPE_IMAGE,
            'video' => File::TYPE_VIDEO,
            'audio' => File::TYPE_AUDIO,
            'document' => File::TYPE_DOCUMENT,
            default => null,
        };
        $wordBody = [
            'uid' => $fresnsResp->getData('uid'),
            'usageType' => $uploadInfoArr['usageType'],
            'usageFsid' => $uploadInfoArr['usageFsid'],
            'archiveCode' => $uploadInfoArr['archiveCode'],
            'type' => $type,
            'extension' => null,
            'size' => null,
            'duration' => null,
        ];
        $permResp = \FresnsCmdWord::plugin('Fresns')->checkUploadPerm($wordBody);

        if ($permResp->isErrorResponse()) {
            $code = $permResp->getCode();
            $message = $permResp->getMessage();

            return response()->view('ImageX::error', compact('code', 'message'), 403);
        }

        // request attributes
        $request->attributes->add([
            'langTag' => $langTag,
            'timezone' => $fresnsResp->getData('timezone'),
            'authUid' => $fresnsResp->getData('uid'),
            'usageType' => $uploadInfoArr['usageType'],
            'usageFsid' => $uploadInfoArr['usageFsid'],
            'fileType' => $uploadInfoArr['fileType'],
            'maxUploadNumber' => $permResp->getData('maxUploadNumber'),

            'aid' => $fresnsResp->getData('aid'),
            'uid' => $fresnsResp->getData('uid'),
        ]);

        // plugin auth info
        $authUlid = (string)Str::ulid();

        CacheHelper::put('imagex', $authUlid, 'fresnsPluginAuth', now()->addMinutes(15));

        Cookie::queue('fresns_plugin_imagex_platform_id', $fresnsResp->getData('platformId'));
        Cookie::queue('fresns_plugin_imagex_lang_tag', $langTag);
        Cookie::queue('fresns_plugin_imagex_auth_ulid', $authUlid);
        Cookie::queue('fresns_plugin_imagex_auth_uid', $fresnsResp->getData('uid'));
        Cookie::queue('fresns_plugin_imagex_file_usage_type', $uploadInfoArr['usageType']);
        Cookie::queue('fresns_plugin_imagex_file_usage_fsid', $uploadInfoArr['usageFsid']);
        Cookie::queue('fresns_plugin_imagex_file_type', $uploadInfoArr['fileType']);

        return $next($request);
    }
}
