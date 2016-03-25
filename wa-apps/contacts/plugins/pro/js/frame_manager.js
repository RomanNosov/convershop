var FrameManager = {
    registerFrame: function (frame) {
        "use strict";

        pm({
            target: window.frames[frame.id],
            type: "register",
            data: {id: frame.id},
            url: frame.contentWindow.location
        });
        pm.bind(frame.id, function (data) {
            var iframe = document.getElementById(data.id);
            if (iframe == null) {
                return;
            }
            iframe.style.height = (data.height + 50).toString() + "px";
        });
    }
};