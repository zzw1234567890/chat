// ws = new WebSocket("ws://119.23.206.106:1111");
// // ws.onopen = function(){
// // //   alert("secuss");
// //   ws.send("ok");
// // };
// ws.onmessage = function(e){
//   console.log("from service : " + e.data);
// }
// function fun(){
//     var data = '{"command":"index","data":{"name":"abc","mode":"单人匹配","num":"123456","pwd":"123456"}}';
//     ws.send(data);
// }
/**
 * 与GatewayWorker建立websocket连接，域名和端口改为你实际的域名端口，
 * 其中端口为Gateway端口，即start_gateway.php指定的端口。
 * start_gateway.php 中需要指定websocket协议，像这样
 * $gateway = new Gateway(websocket://0.0.0.0:7272);
 */
ws = new WebSocket("ws://119.23.206.106:1111");
// 服务端主动推送消息时会触发这里的onmessage
ws.onmessage = function(e){
    // json数据转换成js对象
    console.log(e.data);
    var data = eval("("+e.data+")");
    var type = data.command || '';
    switch(type){
        // Events.php中返回的init类型的消息，将client_id发给后台进行uid绑定
        case 'init':
            // 利用jquery发起ajax请求，将client_id发给后端进行uid绑定
            $.post('http://socket.zhangzw.top/index/build/index', {client_id: data.client_id}, function(data){console.log(data);}, 'json');
            break;
        // 当mvc框架调用GatewayClient发消息时直接alert出来
        default :
            alert(e.data);
    }
};
// $.post('http://socket.zhangzw.top/index/message/index', {}, function(data){console.log(data);}, 'json');