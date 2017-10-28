(function() {
    var appendInput = function appendInput() {
        var input = document.createElement("input"),
            br = document.createElement("br"),
            div = document.querySelector("form > div");
        input.type = "text";
        input.name = "items[]";
        div.appendChild(input);
        div.appendChild(br);
    }
    document.querySelector("input[type=button]").addEventListener("click", appendInput);
})();
