import images from './images.js';
import _ from 'lodash';

function printMe() {
     console.log('I get called from print.js hey yo man!');
}

function component() {
    var element = document.createElement('div');

    element.innerHTML = _.join(['Hello', 'webpack'], ' ');
    element.classList.add('hello');
    var myIcon = new Image();
    myIcon.src = images('icon');
    myIcon.width = '100';
    myIcon.height = '100';
    element.appendChild(myIcon);

    if (process.env.NODE_ENV !== 'production') {
        console.log('Looks like we are in development mode!');
    }

    printMe();

    return element;
}

document.body.appendChild(component());
