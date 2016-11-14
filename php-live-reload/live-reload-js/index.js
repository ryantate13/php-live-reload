var request = require('superagent');

monitorChanges = function(delay, callback, url){
    url = url || '/php-live-reload/live-reload.php';
    delay = delay || 1000;
    delay = (delay < 1000) ? 1000 : delay;
    var defaultCallback = function (err, res){
        if(err){
            console.log(err);
        }
        if (res) {
            if (res.body.time) {
                var delay = res.body.time;
                var changed = res.body.changed;
                if(changed){
                    console.log('change detected');
                    window.setTimeout(function(){window.location.reload();}, delay);
                }
                else{
                    monitorChanges((2 * delay), callback, url);
                }
            }
            else{
                console.log(res);
            }
        }
    };
    callback = callback || defaultCallback;
    window.setTimeout(function(){
        request
            .get(url)
            .end(callback);
    }, delay);
};

module.exports = monitorChanges;