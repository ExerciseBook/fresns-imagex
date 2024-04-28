@extends('ImageX::layouts.master')

@section('content')
    <div style="max-width: 90%; margin: auto; padding-top: 2em">
        <div class="input-group">
            <input class="form-control" type="file" id="formFile"
                   @if( $maxUploadNumber > 1)
                       multiple="multiple" max="{{ $maxUploadNumber }}"
                   @endif
                   accept="{{ $uploadConfig['inputAccept'] }}">
            <button class="btn btn-outline-secondary ajax-progress-submit" type="button"
                    id="do-upload-btn">{{ $fsLang['uploadButton'] }}</button>
        </div>
        <div class="ajax-progress progress mt-2"></div>


        <div class="mx-2 mt-3 text-secondary fs-7" id="extensions" data-value="{{ $extensionNames }}">
            {{ $fsLang['uploadTipExtensions'] }}: {{ $extensionNames }}
        </div>
        <div class="mx-2 mt-2 text-secondary fs-7" id="uploadMaxSize" data-value="{{ $maxSize }}">
            {{ $fsLang['uploadTipMaxSize'] }}: {{ $maxSize }} MB
        </div>
        @if ($fileType == 'video' || $fileType == 'audio')
            <div class="mx-2 mt-2 text-secondary fs-7" id="uploadMaxTime" data-value="{{ $maxDuration }}">
                {{ $fsLang['uploadTipMaxDuration'] }}: {{ $maxDuration }} {{ $fsLang['unitSecond'] }}
            </div>
        @endif
        <div class="mx-2 my-2 text-secondary fs-7" id="uploadFileMax" data-value="{{ $maxUploadNumber }}">
            {{ $fsLang['uploadTipMaxNumber'] }}: {{ $maxUploadNumber }}
        </div>

    </div>
@endsection

@push('script')
    <script>
        TaskInfo.postMessageKey = '{{ $postMessageKey }}';

        const loadVideo = file => new Promise((resolve, reject) => {
            try {
                let video = document.createElement('video')
                video.preload = 'metadata'
                video.onloadedmetadata = function () {
                    resolve(this)
                }
                video.onerror = function () {
                    reject("Invalid video. Please select a video file.")
                }
                video.src = window.URL.createObjectURL(file)
            } catch (e) {
                reject(e)
            }
        })

        const fileInput = document.querySelector("input[type=file]");
        fileInput.addEventListener("change", async function () {
            let video_file = document.getElementById('formFile').files[0];
            if (this.files.length > {{ $maxUploadNumber  }}) {
                alert("{{ $fsLang['uploadTipMaxNumber'] }}: {{ $maxUploadNumber }}");
                this.value = "";
            }
            for (let i = 0; i <= this.files.length - 1; i++) {
                const fsize = this.files.item(i).size;
                const file = Math.round((fsize / 1024 / 1024));
                if (file >= {{ $uploadConfig['maxSize'] }}) {
                    alert("{{ $fsLang['uploadTipMaxSize'] }} : {{ $uploadConfig['maxSize'] }} MB");
                    this.value = "";
                }

                @if ($fileType == 'video' || $fileType == 'audio')
                    try {
                    const video = await loadVideo(this.files[i])

                    const videoDuration = video.duration
                    if (typeof videoDuration === "number" && !isNaN(videoDuration) && parseInt(videoDuration) > {{ $maxDuration }}) {
                        alert("{{ $fsLang['uploadWarningMaxDuration'] }}: {{ $maxDuration }} {{ $fsLang['unitSecond'] }}");
                        this.value = "";
                    }
                } catch (e) {
                    console.error("get video duration failed", e)
                }
                @endif
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
                window.tips('{{ $fsLang['editorNoSelectGroup'] }}');
                return;
            }

            applyUploadToken({
                filesCount: fileInput.files.length,
                type: {{ $typeInt }}
            }).then(e => {
                return uploadFile(ttUploader, fileInput.files, e)
            }).then(async e => {
                await Promise.all(e.promiseHandler)
                let callbackAction = {
                    postMessageKey: '',
                    windowClose: true,
                    redirectUrl: '',
                    dataHandler: '',
                };
                FresnsCallback.send(callbackAction);
            }).catch((e) => {
                window.tips(e.message, e.code)
                progressExit && progressExit();
            });
        }
    </script>
@endpush
