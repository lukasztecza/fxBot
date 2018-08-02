import '../sass/index.scss';
import Default from '../images/default.png';
import Icon from '../images/lukasztecza.jpg';

export default function images(name) {
    switch (name) {
        case 'icon': return Icon;
        default: return Default;
    }
}
