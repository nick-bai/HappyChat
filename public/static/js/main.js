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

        sendMessage();
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
function sendMessage () {

    layui.use(['laytpl'], function () {

        var laytpl = layui.laytpl;

        var getTpl = mine.innerHTML
            ,view = document.getElementById('chat-area');

        laytpl(getTpl).render({
            uid: uid,
            name: name,
            avatar: avatar,
            time: new Date().format("yyyy-MM-dd hh:mm:ss"),
            message: $("#textarea").val()
        }, function(html){
            view.innerHTML += html;
            wordBottom();
        });

        socket.emit("NEW_MESSAGE", JSON.stringify({content: $("#textarea").val()}), function (res) {
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