var ajaxurl = localscript.ajaxurl;

var mpxHelper = {   
    getVideos: function(range, callback){

        var data = {
            _wpnonce: theplatform.tp_nonce,
            action: 'get_videos',
            range: range,
            query: tpHelper.queryString,            
            isEmbed: tpHelper.isEmbed,
            myContent: jQuery('#my-content-cb').prop('checked')
        };
    
        jQuery.post(ajaxurl, data, function(resp){
            resp = JSON.parse(resp);
            if (resp.isException)
                displayMessage(resp.description);
            else{
               callback(resp);
            }
        });
    },    

    buildMediaQuery: function (data){

        var queryParams = '';
        if (data.category)
            queryParams = queryParams.appendParams({byCategories: data.category});

        if (data.search){
            queryParams = queryParams.appendParams({q: encodeURIComponent(data.search)});
            data.sort = ''; //Workaround because solr hates sorts.
        }

        if (data.sort){
            var sortValue = data.sort + (data.desc ? '|desc' : '');
            queryParams = queryParams.appendParams({sort: sortValue});
        }
    
        if (data.selectedGuids)
            queryParams = queryParams.appendParams({byGuid: data.selectedGuids});

        return queryParams;
    },

    getCategoryList: function (callback){        
        var data = {
            _wpnonce: theplatform.tp_nonce,
            action: 'get_categories',
            sort: 'order',
            fields: 'title'                        
        };
    
        jQuery.post(ajaxurl, data,            
            function(resp){
                callback(JSON.parse(resp));
            });
    },

    //Retrieve parameters from the original request.
    getParameters: function (str) {
        var searchString ='';
        if (str && str.length > 0){
            if (str.indexOf('?') < 0 )
                return {};
            else
                searchString = str.substring(str.indexOf('?') + 1);
        }else
            searchString = window.location.search.substring(1);

        var params = searchString.split("&")
        ,   hash = {};

        if (searchString == "") return {};
        for (var i = 0; i < params.length; i++) {
            var val = params[i].split("=");
            hash[decodeURIComponent(val[0])] = decodeURIComponent(val[1]);
        }
        return hash;
    },

    //Get a list of release URls
    extractVideoUrlfromMedia: function (media){
        var res = [];

        if (media.entries)
            media = media['entries'].shift(); //We always only grab the first media in the list THIS SHOULD BE THE ONLY MEDIA.

        if (media && media.content)
            media = media.content;
        else
            return res;

        for (var contentIdx in media){
            var content = media[contentIdx];
            if ((content.contentType == "video" || content.contentType == "audio") && content.releases) {
                for (var releaseIndex in content.releases) {
                    if (content.releases[releaseIndex].delivery == "streaming")
                        res.push(content.releases[releaseIndex].pid);    
                }
            }
            
        }

        return res;
    }
};

//Make my life easier by prototyping this into the string.
String.prototype.appendParams = function (params){
    var updatedString = this;
    for (var key in params){
        if (updatedString.indexOf(key+'=') > -1)
            continue;

        // if (updatedString.indexOf('?') > -1)
            updatedString += '&'+key+'='+params[key];
        // else
        //     updatedString += '?'+key+'='+params[key];
    }
    return updatedString;
};