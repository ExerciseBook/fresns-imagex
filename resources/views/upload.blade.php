@extends('ImageX::layouts.master')

@section('content')
    @if ($fileCount >= $uploadConfig['uploadNumber'])
        <div class="alert alert-danger" role="alert">
            {{ $fileCountTip }}
        </div>
    @else
        <div class="input-group">
            <input class="form-control" type="file" id="formFile"
                   @if($uploadConfig['uploadNumber'] > 1)
                       multiple="multiple" max="{{ $fileMax }}"
                   @endif accept="{{ $uploadConfig['inputAccept'] }}">
            <button class="btn btn-outline-secondary ajax-progress-submit"
                    type="button" id="do-upload-btn">{{ $fsLang['editorUploadBtn'] }}</button>
        </div>
        <div class="ajax-progress progress mt-2"></div>
    @endif

    <div class="mx-2 mt-3 text-secondary fs-7" id="extensions"
         data-value="{{ $uploadConfig['extensions'] }}">{{ $fsLang['editorUploadExtensions'] }}
        : {{ $uploadConfig['extensions'] }}</div>
    <div class="mx-2 mt-2 text-secondary fs-7" id="uploadMaxSize"
         data-value="{{ $uploadConfig['maxSize'] }}">{{ $fsLang['editorUploadMaxSize'] }}
        : {{ $uploadConfig['maxSize'] }} MB
    </div>
    @if ($uploadConfig['maxTime'] > 0)
        <div class="mx-2 mt-2 text-secondary fs-7" id="uploadMaxTime"
             data-value="{{ $uploadConfig['maxTime'] }}">{{ $fsLang['editorUploadMaxTime'] }}
            : {{ $uploadConfig['maxTime'] }} {{ $fsLang['unitSecond'] }}</div>
    @endif
    <div class="mx-2 my-2 text-secondary fs-7" id="uploadFileMax"
         data-value="{{ $fileMax }}">{{ $fsLang['editorUploadNumber'] }}: {{ $fileMax }}</div>
@endsection

@push('script')
    <script>
        const fileInput = document.querySelector("input[type=file]");
        fileInput.addEventListener("change", function () {
            if (this.files.length > {{ $fileMax }}) {
                alert("{{ $fsLang['editorUploadNumber'] }}: {{ $fileMax }}");
                this.value = "";
            }
        });

        const uploadBtn = document.querySelector("#do-upload-btn")
        uploadBtn.onclick = function (e) {
            try {
                fileInput.disabled = true;
                uploadBtn.disabled = true;
                doUpload(e)
            } catch (p) {

            } finally {
                fileInput.disabled = false;
                uploadBtn.disabled = false;
            }
        }

        const ttUploader = new TTUploader({
            appId: {{ $imagexClientAppId }},
            userId: '{{ $checkHeaders['aid'] }}',
            imageConfig: {
                serviceId: '{{ $imagexServiceId }}',
            }
        });

        ttUploader.on('progress', onUploadProgress)
        ttUploader.on('complete', onUploadCompleted)
        const uploadSessionId = '{{ $uploadSessionId }}'
        const aid = '{{ $checkHeaders['aid'] }}'
        const uid = '{{ $checkHeaders['uid'] }}'

        function doUpload(e) {
            if (fileInput.files.length === 0) {
                window.tips("未选择文件");
                return;
            }

            applyUploadToken({
                filesCount: fileInput.files.length,
                type: {{ $fileType }}
            }).then((e) => {
                return uploadFile(ttUploader, fileInput.files, e)
            }).catch((e) => {
                window.tips(e.message, e.code)
                progressExit() && progressExit();
            });
        }
    </script>
@endpush

