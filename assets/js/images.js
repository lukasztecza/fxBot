import '../sass/index.scss';
import Default from '../images/default_image.png';
import Icon from '../images/test_image.png';

export default function images(name) {
    switch (name) {
        case 'icon': return Icon;
        default: return Default;
    }
}
