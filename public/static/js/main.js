var customerPool = [];

var socket = io(window.location.host + ':2020?token=' + token);

socket.on('connect', function(){

    layui.use(['layer'], function () {
        var layer = layui.layer;
        layer.ready(function () {
            layer.msg('欢迎进入 HappyChat聊天室');
        });
    });

    // 进入聊天室
    socket.emit("ADD_USER", JSON.stringify({token: token}), function (data) {
        if (400 == data.code) {
            window.location.href = '/index/login';
        } else if(0 == data.code) {

            if (data.data.length > 0) {
                $.each(data.data, function (k, v) {
                    showUserList(v);

                    customerPool.push(v.uid);
                });
            }
        }
    });

    // 用户进入
    socket.on("user joined", function (data) {
        console.log(customerPool);
        if(-1 != $.inArray(data.uid, customerPool)) {
            return false;
        }

        customerPool.push(data.uid);
        showUserList(data);
    });

    // 展示消息
    socket.on("new message", function (data) {
        $('#ding')[0].play();
        showMessage(data);

        if(document.hidden){
            showNotice(data.avatar, '您有新消息', data.message);
        }
    });

    // 断开连接
    socket.on("user left", function (data) {
        console.log(data);
        $.each(customerPool, function (k, v) {
            if(v == data.uid) {
                customerPool.splice(k, 1);
                $("#u-" + data.uid).remove();
            }
        });
    });
});

$(function () {

    $("#sendBtn").click(function () {

        var data = $("#textarea").val();
        if (data == '') {
            layer.msg("请输入内容");
            return false;
        }

        sendMessage('');
        $("#sendBtn").removeClass('active');
    });

    // 输入监听
    $("#textarea").keyup(function () {
        var len = $(this).val().length;
        if(len == 0) {
            $("#sendBtn").removeClass('active');
        } else if(len >0 && !$("#sendBtn").hasClass('active')) {
            $("#sendBtn").addClass('active');
        }
    });

    // 点击表情
    var index;
    $("#face").click(function (e) {
        e.stopPropagation();
        layui.use(['layer'], function () {
            var layer = layui.layer;

            var isShow = $(".layui-whisper-face").css('display');
            if ('block' == isShow) {
                layer.close(index);
                return;
            }
            var height = $(".chat-body").height() - 110;
            layer.ready(function () {
                index = layer.open({
                    type: 1,
                    offset: [height + 'px', '240px'],
                    shade: false,
                    title: false,
                    closeBtn: 0,
                    area: '395px',
                    content: showFaces()
                });
            });
        });
    });
});

// 监听快捷键发送
document.getElementById('textarea').addEventListener('keydown', function (e) {
    if (e.keyCode != 13) return;
    e.preventDefault();  // 取消事件的默认动作
    sendMessage('');
});

// 滚动到最底端
function wordBottom () {
    var box = $(".chat-box");
    box.scrollTop(box[0].scrollHeight);
}

// 发送消息
function sendMessage (inMsg) {

    layui.use(['laytpl'], function () {

        var laytpl = layui.laytpl;

        var getTpl = mine.innerHTML
            ,view = document.getElementById('chat-area');

        if('' == inMsg) {
            var input = $("#textarea").val();
        } else {
            var input = inMsg;
        }

        laytpl(getTpl).render({
            uid: uid,
            name: name,
            avatar: avatar,
            time: new Date().format("yyyy-MM-dd hh:mm:ss"),
            message: input
        }, function(html){
            view.innerHTML += html;
            wordBottom();
        });

        socket.emit("NEW_MESSAGE", JSON.stringify({content: input}), function (res) {
            console.log(res);
            $("#textarea").val('');
        });
    })
}

// 展示消息
function showMessage (data) {

    layui.use(['laytpl'], function () {

        var laytpl = layui.laytpl;

        var getTpl = other.innerHTML
            ,view = document.getElementById('chat-area');

        laytpl(getTpl).render(data, function(html){
            view.innerHTML += html;
            wordBottom();
        });
    });
}

// 展示访客列表
function showUserList (data) {

    layui.use(['laytpl'], function () {

        var laytpl = layui.laytpl;

        var getTpl = list.innerHTML
            ,view = document.getElementById('visitor-list');

        laytpl(getTpl).render(data, function(html){
            view.innerHTML += html;
        });
    });
}

function showNotice(head, title, msg) {
    var Notification = window.Notification || window.mozNotification || window.webkitNotification;
    if (Notification) {
        Notification.requestPermission(function (status) {
            //status默认值'default'等同于拒绝 'denied' 意味着用户不想要通知 'granted' 意味着用户同意启用通知
            if ("granted" != status) {
                return;
            } else {
                var tag = "sds" + Math.random();
                var notify = new Notification(
                    title,
                    {
                        dir: 'auto',
                        lang: 'zh-CN',
                        tag: tag,//实例化的notification的id
                        icon: '/' + head,//通知的缩略图,//icon 支持ico、png、jpg、jpeg格式
                        body: msg //通知的具体内容
                    }
                );
                notify.onclick = function () {
                    //如果通知消息被点击,通知窗口将被激活
                    window.focus();
                },
                    notify.onerror = function () {
                        console.log("HTML5桌面消息出错！！！");
                    };
                notify.onshow = function () {
                    setTimeout(function () {
                        notify.close();
                    }, 2000)
                };
                notify.onclose = function () {
                    console.log("HTML5桌面消息关闭！！！");
                };
            }
        });
    } else {
        console.log("您的浏览器不支持桌面消息");
    }
}

// 转义聊天内容中的特殊字符
var replaceContent = function(content) {
    // 支持的html标签
    var html = function (end) {
        return new RegExp('\\n*\\[' + (end || '') + '(pre|div|span|p|table|thead|th|tbody|tr|td|ul|li|ol|li|dl|dt|dd|h2|h3|h4|h5)([\\s\\S]*?)\\]\\n*', 'g');
    };
    content = (content || '').replace(/&(?!#?[a-zA-Z0-9]+;)/g, '&amp;')
        .replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/'/g, '&#39;').replace(/"/g, '&quot;') // XSS
        .replace(/@(\S+)(\s+?|$)/g, '@<a href="javascript:;">$1</a>$2') // 转义@

        .replace(/face\[([^\s\[\]]+?)\]/g, function (face) {  // 转义表情
            var alt = face.replace(/^face/g, '');
            return '<img alt="' + alt + '" title="' + alt + '" src="' + faces[alt] + '">';
        })
        .replace(/img\[([^\s]+?)\]/g, function (img) {  // 转义图片
            return '<img class="layui-whisper-photos" src="' + img.replace(/(^img\[)|(\]$)/g, '') + '" width="150px" height="150px">';
        })
        .replace(/file\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { // 转义文件
            var href = (str.match(/file\(([\s\S]+?)\)\[/) || [])[1];
            var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
            if (!href) return str;
            return '<a class="layui-whisper-file" href="' + href + '" download target="_blank"><i class="layui-icon">&#xe61e;</i><cite>' + (text || href) + '</cite></a>';
        })
        .replace(/audio\[([^\s]+?)\]/g, function(audio){  //转义音频
            return '<audio src="' + audio.replace(/(^audio\[)|(\]$)/g, '') + '" controls="controls" style="width: 200px;height: 20px"></audio>';
        })
        .replace(/a\([\s\S]+?\)\[[\s\S]*?\]/g, function (str) { // 转义链接
            var href = (str.match(/a\(([\s\S]+?)\)\[/) || [])[1];
            var text = (str.match(/\)\[([\s\S]*?)\]/) || [])[1];
            if (!href) return str;
            return '<a href="' + href + '" target="_blank">' + (text || href) + '</a>';
        }).replace(html(), '\<$1 $2\>').replace(html('/'), '\</$1\>') // 转移HTML代码
        .replace(/\n/g, '<br>');// 转义换行

    return content;
}
// 表情对应数组
var getFacesIcon = function () {
    return ["[微笑]", "[嘻嘻]", "[哈哈]", "[可爱]", "[可怜]", "[挖鼻]", "[吃惊]", "[害羞]", "[挤眼]", "[闭嘴]", "[鄙视]",
        "[爱你]", "[泪]", "[偷笑]", "[亲亲]", "[生病]", "[太开心]", "[白眼]", "[右哼哼]", "[左哼哼]", "[嘘]", "[衰]",
        "[委屈]", "[吐]", "[哈欠]", "[抱抱]", "[怒]", "[疑问]", "[馋嘴]", "[拜拜]", "[思考]", "[汗]", "[困]", "[睡]",
        "[钱]", "[失望]", "[酷]", "[色]", "[哼]", "[鼓掌]", "[晕]", "[悲伤]", "[抓狂]", "[黑线]", "[阴险]", "[怒骂]",
        "[互粉]", "[心]", "[伤心]", "[猪头]", "[熊猫]", "[兔子]", "[ok]", "[耶]", "[good]", "[NO]", "[赞]", "[来]",
        "[弱]", "[草泥马]", "[神马]", "[囧]", "[浮云]", "[给力]", "[围观]", "[威武]", "[奥特曼]", "[礼物]", "[钟]",
        "[话筒]", "[蜡烛]", "[蛋糕]"]
};

// 表情替换
var faces = function () {
    var alt = getFacesIcon(), arr = {};
    $.each(alt, function (index, item) {
        arr[item] = '/static/images/face/' + index + '.gif';
    });
    return arr;
}();

// 展示表情
var showFaces = function () {
    var alt = getFacesIcon();
    var _html = '<div class="layui-whisper-face"><ul class="layui-clear whisper-face-list">';
    $.each(alt, function (index, item) {
        _html += '<li title="' + item + '" onclick="checkFace(this)"><img src="/static/images/face/' + index + '.gif" /></li>';
    });
    _html += '</ul></div>';

    return _html;
};

// 选择表情
var checkFace = function (obj) {
    var word = $("#textarea").val() + ' face' + $(obj).attr('title') + ' ';
    $("#textarea").val(word).focus();

    $(".layui-whisper-face").hide();
    $(".send-input").addClass('active');
};


// 格式化时间
Date.prototype.format = function(fmt) {
    var o = {
        "M+": this.getMonth()+1,                 // 月份
        "d+": this.getDate(),                    // 日
        "h+": this.getHours(),                   // 小时
        "m+": this.getMinutes(),                 // 分
        "s+": this.getSeconds(),                 // 秒
        "q+": Math.floor((this.getMonth()+3)/3), // 季度
        "S": this.getMilliseconds()             // 毫秒
    };

    if(/(y+)/.test(fmt)) {
        fmt = fmt.replace(RegExp.$1, (this.getFullYear()+"").substr(4 - RegExp.$1.length));
    }

    for(var k in o) {
        if(new RegExp("("+ k +")").test(fmt)){
            fmt = fmt.replace(RegExp.$1, (RegExp.$1.length==1) ? (o[k]) : (("00"+ o[k]).substr((""+ o[k]).length)));
        }
    }

    return fmt;
};

var showBigPic = function () {

    $(".layui-whisper-photos").on('click', function () {
        var src = this.src;
        layer.photos({
            photos: {
                data: [{
                    "alt": "大图模式",
                    "src": src
                }]
            }
            , shade: 0.5
            , closeBtn: 2
            , anim: 0
            , resize: false
            , success: function (layero, index) {

            }
        });
    });
};

// 图片上传
layui.use(['upload', 'layer'], function () {
    var upload = layui.upload;
    var layer = layui.layer;

    var index;
    upload.render({
        elem: '#image'
        , accept: 'images'
        , exts: 'jpg|jpeg|png|gif'
        , url: '/index/upload/uploadImg/token/' + token
        , before: function () {
            index = layer.load(0, {shade: false});
        }
        , done: function (res) {
            layer.close(index);
            sendMessage('img[' + res.data.src + ']');
            showBigPic();
        }
        , error: function () {
            // 请求异常回调
        }
    });
});