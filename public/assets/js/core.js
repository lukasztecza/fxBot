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
//@TODO add webpack and npm for dev env so it builds assets from View part using versioning variable
