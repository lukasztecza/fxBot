import images from './images.js';
import _ from 'lodash';

function printMe() {
     console.log('I get called from print.js hey yo man!');
}

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

    if (process.env.NODE_ENV !== 'production') {
        console.log('Looks like we are in development mode!');
    }

    printMe();

    return element1;
}

document.body.appendChild(component());
