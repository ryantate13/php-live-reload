var request = require('superagent');

monitorChanges = function(minTimeToCheck, callback, url){
    minTimeToCheck = minTimeToCheck || 1000;
    minTimeToCheck = (minTimeToCheck < 1000) ? 1000 : minTimeToCheck;
    callback = callback || function(err, res){ if(err){console.log(err)};if(res.body && res.body.changed){window.location.reload()}; };
    window.setTimeout(function(){
        request
            .get(url)
            .end(callback);
    }, minTimeToCheck);
};

module.exports = monitorChanges;