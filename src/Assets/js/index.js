import _ from 'lodash';
import printMe from './print.js';
import '../sass/main.scss';
import '../sass/core.scss';
import Icon from '../images/lukasztecza.png';

//TODO index.js should be entry which includes one index.scss
// index.js includes also all other js files and images
// index.scss includes all other styles
function component() {
    var element = document.createElement('div');

    element.innerHTML = _.join(['Hello', 'webpack'], ' ');
    element.classList.add('hello');

    var myIcon = new Image();
    myIcon.src = Icon;
    element.appendChild(myIcon);

    if (process.env.NODE_ENV !== 'production') {
        console.log('Looks like we are in development mode!');
    }

    printMe();

    return element;
}

document.body.appendChild(component());
