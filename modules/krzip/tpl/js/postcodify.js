
jQuery(function() {
    
    // jQuery를 $로 사용할 수 있도록 한다.
    
    var $ = jQuery;
    
    // Postcodify 팝업 레이어 플러그인을 로딩한다.
    
    var cdnProtocol = navigator.userAgent.match(/MSIE [56]\./) ? "http:" : "";
    if (cdnProtocol === "" && !window.location.protocol.match(/^https?/)) cdnProtocol = "http:";
    $.ajaxSetup({ cache : true });
    $.getScript(cdnProtocol + "//cdn.poesis.kr/post/search.min.js");
    $.ajaxSetup({ cache : false });
    
    // 메인 CDN에서 로딩할 수 없는 경우 백업 CDN에서 로딩한다.
    
    setTimeout(function() {
        if (typeof $.fn.postcodify === "undefined" || typeof $.fn.postcodifyPopUp === "undefined") {
            $.ajaxSetup({ cache : true });
            $.getScript(cdnProtocol + "//d1p7wdleee1q2z.cloudfront.net/post/search.min.js");
            $.ajaxSetup({ cache : false });
        }
    }, 2000);
    
    // 검색 단추에 이벤트를 부착한다.
    
    $("button.postcodify_search_button").each(function() {
        
        // 초기화가 완료될 때까지 검색 버튼 클릭을 금지한다.
        
        var searchButton = $(this).data("initialized", "N");
        searchButton.on("click", function() {
            alert($(this).data("not-loaded-yet"));
        });
        
        // 부모 <div>의 크기에 따라 검색창의 크기를 조절한다.
        
        var container = $(this).parents("div.postcodify_address_area");
        var postcodeInput = container.find("input.postcodify.postcode");
        var inputs = container.find("input.postcodify").not(".postcode");
        var labels = container.find("label.postcodify").not(".postcode");
        var windowResizeCallback = function() {
            var width = container.width();
            postcodeInput.width(Math.min(206, width - 100));
            if (width < 360) {
                inputs.width(width - 16);
                labels.hide();
            } else {
                inputs.width(Math.min(540, width - 100));
                labels.show();
            }
        };
        $(window).resize(windowResizeCallback);
        windowResizeCallback();
        
        // 설정을 가져온다.
        
        var serverUrl = container.data("server-url");
        var mapLinkProvider = container.data("map-provider");
        var addressFormat = container.data("address-format");
        var postcodeFormat = parseInt(container.data("postcode-format"), 10);
        var serverRequestFormat = container.data("server-request-format");
        var requireExactQuery = container.data("require-exact-query");
        var useFullJibeon = container.data("use-full-jibeon");
        
        if (postcodeFormat == 5) {
            container.find("input.postcodify.postcode").addClass("postcodify_postcode5");
        } else {
            container.find("input.postcodify.postcode").addClass("postcodify_postcode6");
        }
        
        // 팝업 레이어 플러그인 로딩이 끝날 때까지 기다렸다가 셋팅한다.
        
        var waitInterval;
        waitInterval = setInterval(function() {
            if (typeof $.fn.postcodify === "undefined" || typeof $.fn.postcodifyPopUp === "undefined") {
                return;
            } else {
                clearInterval(waitInterval);
            }
            if (searchButton.data("initialized") !== "Y") {
                searchButton.data("initialized", "Y").off("click").postcodifyPopUp({
                    api : serverUrl,
                    inputParent : container,
                    mapLinkProvider : mapLinkProvider,
                    useCors : (serverRequestFormat !== "JSONP"),
                    useFullJibeon : (useFullJibeon === "Y"),
                    requireExactQuery : (requireExactQuery === "Y"),
                    forceDisplayPostcode5 : (postcodeFormat == 5),
                    onSelect : function() {
                        var postcode = container.find("input.postcodify.postcode").val();
                        container.find("div.postcodify_hidden_fields").show();
                        if (addressFormat === "postcodify" || addressFormat === "newkrzip") {
                            container.find("input.postcodify.postcode").removeAttr("readonly").off("click");
                        }
                        if (addressFormat === "newkrzip") {
                            var jibeon_input = container.find("input.postcodify_jibeon_address");
                            if (jibeon_input.val()) {
                                 jibeon_input.val("(" + jibeon_input.val() + ")");
                            }
                        }
                        if (addressFormat === "oldkrzip3") {
                            var extra_input = container.find("input.postcodify_extra_info");
                            extra_input.val(extra_input.val() + " (" + postcode + ")");
                        }
                        if (addressFormat === "oldkrzip2") {
                            var addr_input = container.find("input.postcodify_address");
                            var extra_info = container.find("input.postcodify_extra_info").val();
                            addr_input.val(addr_input.val() + " " + extra_info + " (" + postcode + ")");
                        }
                    }
                });
            }
        }, 100);
        
        // 주소를 처음 입력하는 경우 우편번호 입력란을 클릭하면 자동으로 팝업 레이어가 나타나도록 한다.
        
        container.find("input.postcodify.postcode").each(function() {
            if ($(this).attr("readonly")) {
                $(this).on("click", function() {
                    container.find("button.postcodify_search_button").click();
                });
            }
        });
    });
});
