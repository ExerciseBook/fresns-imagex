const setTimeoutToastHide = () => {
    // 躺平
    $('.toast.show')?.each((k, v) => {
        setTimeout(function () {
            $(v).hide();
        }, 1500);
    });
};

window.tips = function (message, code = 200) {
    let html = `<div aria-live="polite" aria-atomic="true" class="position-fixed top-50 start-50 translate-middle" style="z-index:9999">
          <div class="toast show" role="alert" aria-live="assertive" aria-atomic="true">
              <div class="toast-header">
                  <img src="/static/images/icon.png" width="20px" height="20px" class="rounded me-2" alt="Fresns">
                  <strong class="me-auto">Fresns</strong>
                  <small>${code}</small>
                  <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
              </div>
              <div class="toast-body">${message}</div>
          </div>
      </div>`;
    $('div.fresns-tips').prepend(html);
    setTimeoutToastHide();
};

// progress
// progress-bar 将会作为 "<div class="ajax-progress progress mt-2"></div>" 的子元素添加到尾部
// 使用时，需要在页面中增加代码 <div class="ajax-progress progress mt-2"></div>
// 并在页面的相关按钮增加 class 样式 ajax-progress-submit
// 在相关代码中调用:
// progressInit && progressInit();
// progressReset() && progressReset();
// progressDown() && progressDown();
// progressExit() && progressExit();
window.progress = {
    total: 100,
    valuenow: 0,
    speed: 1000,
    parentElement: null,
    stop: false,
    html: function () {
        return `<div class="progress-bar" role="progressbar" style="width: ${progress.valuenow}%" aria-valuenow="${progress.valuenow}" aria-valuemin="0" aria-valuemax="100">${progress.valuenow}</div>`;
    },
    setProgressElement: function (pe) {
        this.parentElement = pe;
        return this;
    },
    init: function () {
        this.total = 100;
        this.valuenow = 0;
        this.parentElement = null;
        this.stop = false;
        return this;
    },
    work: function () {
        this.add(progress);
    },
    add: function (obj) {
        var html = obj.html();

        if (obj.stop !== true && obj.valuenow < obj.total) {
            let num = parseFloat(obj.total) - parseFloat(obj.valuenow);
            obj.valuenow = (parseFloat(obj.valuenow) + parseFloat(num / 100)).toFixed(2);
            $(obj.parentElement).empty().append(html);
        } else {
            $(obj.parentElement).empty().append(html);
            return;
        }
        setTimeout(function () {
            obj.add(obj);
        }, obj.speed);
    },
    exit: function () {
        this.valuenow = 0;
        this.stop = true;
        return this;
    },
    done: function () {
        this.valuenow = this.total;
        return this;
    },
    clearHtml: function () {
        this.parentElement?.empty();
    },
};

function progressInit() {
    var progressObj = progress.init();
    var ele = $('.ajax-progress').removeClass('d-none');
    if (ele.length > 0) {
        progressObj.setProgressElement(ele[0]);
        progressObj.work();
    }
}

function progressReset() {
    $('.ajax-progress').empty();
    $('.ajax-progress-submit').show().removeAttr('disabled');
}

function progressDown() {
    progress.done();
}

function progressExit() {
    progress.exit();
}

function applyUploadToken(data) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: '/api/imagex/files',
            method: 'post',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
            data: data,
            success(data) {
                // noinspection EqualityComparisonWithCoercionJS
                if (data.code != 0) {
                    reject(data);
                    return;
                }

                resolve(data.data);
            },
            error(err) {
                reject(err.responseJSON);
            },
        });
    });
}

const FileInfo = {};

/**
 * @param {TTUploader} ttUploader
 * @param {FileList} fileList
 * @param {array} d
 */
function uploadFile(ttUploader, fileList, d) {
    progressInit() && progressInit();
    const uploadInfo = d['uploadInfo']
    for (let i = 0; i < fileList.length; i++) {
        const key = ttUploader.addImageFile({
            file: fileList[i],
            stsToken: uploadInfo[i],
            storeKey: uploadInfo[i]['storeKey'],
        })
        FileInfo[key] = uploadInfo[i]
        ttUploader.start(key)
    }
}

function onUploadProgress(data) {

}

function onUploadCompleted(data) {
    progressDown() && progressDown();

    const key = data.key;
    const fileInfo = FileInfo[key];

    $.ajax({
        url: '/api/imagex/files/' + fileInfo['AccessKeyID'] + '?session=' + uploadSessionId,
        method: 'patch',
        headers: {
            Accept: 'application/json',
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
        },
        data: {
            'uploadResult': data.uploadResult,
        },
        success(res) {
            let searchParams = new URLSearchParams(window.location.href);

            const fresnsCallbackMessage = {
                code: 0, // 处理状态，0 表示成功，其余为失败状态码
                message: 'ok', // 失败时的提示信息
                action: {
                    postMessageKey: searchParams.get('postMessageKey'), // 路径中 postMessageKey 变量值
                    windowClose: true, // 是否关闭窗口或弹出层(modal)
                    redirectUrl: '', // 是否重定向新页面
                    dataHandler: 'add' // 是否处理数据: add, remove, reload
                },
                data: res.data,
            }

            const messageString = JSON.stringify(fresnsCallbackMessage);
            const userAgent = navigator.userAgent.toLowerCase();

            switch (true) {
                case (window.Android !== undefined):
                    // Android (addJavascriptInterface)
                    window.Android.receiveMessage(messageString);
                    break;

                case (window.webkit && window.webkit.messageHandlers.iOSHandler !== undefined):
                    // iOS (WKScriptMessageHandler)
                    window.webkit.messageHandlers.iOSHandler.postMessage(messageString);
                    break;

                case (window.FresnsJavascriptChannel !== undefined):
                    // Flutter
                    window.FresnsJavascriptChannel.postMessage(messageString);
                    break;

                case (window.ReactNativeWebView !== undefined):
                    // React Native WebView
                    window.ReactNativeWebView.postMessage(messageString);
                    break;

                case (userAgent.indexOf('miniprogram') > -1):
                    // WeChat Mini Program
                    wx.miniProgram.postMessage({ data: messageString });
                    wx.miniProgram.navigateBack();
                    break;

                // Web
                default:
                    parent.postMessage(messageString, '*');
            }

            console.log('发送给父级的信息', fresnsCallbackMessage);
        },
        error(e) {
            window.tips(e.message, e.code)
        },
    });
}
