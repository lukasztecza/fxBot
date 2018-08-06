import images from './images.js';
import _ from 'lodash';

function component() {
    var element1 = document.createElement('div');
    var element2 = document.createElement('div');

    element2.style.height = '300px';
    element2.style.width = '300px';
    element2.style.border = '1px solid black';
    element2.innerHTML = _.join(['Hello', 'webpack'], ' ');
    element2.classList.add('hello');

    var myIcon = new Image();
    myIcon.src = images('icon');
    myIcon.width = '300';
    myIcon.height = '300';

    element1.appendChild(element2);
    element1.appendChild(myIcon);

    return element1;
}

if (document.getElementById('home') !== null) {
    document.body.appendChild(component());
}
