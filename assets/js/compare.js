import Chartist from 'chartist';

function drawChart() {
    var dataRows = document.getElementById('data-set').querySelectorAll('tbody > tr'),
        length = dataRows.length,
        dataValues,
        labels = [],
        data1 = [],
        data2 = [],
        data3 = [],
        title1,
        title2,
        title3
    ;

    while (length--) {
        dataValues = dataRows[length].getElementsByTagName('td');
        labels.push(dataValues[0].innerHTML);
        data1.push(dataValues[2].innerHTML);
        title1 = dataValues[1].innerHTML;

        if (dataValues[1].innerHTML.indexOf(dataValues[3].innerHTML) === 0) {
            title2 = dataValues[3].innerHTML;
            data2.push(dataValues[4].innerHTML);
        } else {
            data2.push(null);
        }
        if (dataValues[1].innerHTML.indexOf(dataValues[3].innerHTML) === 4) {
            title3 = dataValues[3].innerHTML;
            data3.push(dataValues[4].innerHTML);
        } else {
            data3.push(null);
        }
    }

    var options = {
        height: 400,
        axisX: {
            labelInterpolationFnc: function skipLabels(value, index) {
                return index % 10  === 0 ? value : null;
            }
        },
        showLine: false
    };

    var chart = new Chartist.Line(
        '#price-chart',
        {
            labels: labels,
            series: [{
                name: 'series1',
                data: data1
            }]
        },
        options
    );
    var chart = new Chartist.Line(
        '#compare-chart',
        {
            labels: labels,
            series: [{
                name: 'series2',
                data: data2
            }, {
                name: 'series3',
                data: data3
            }]
        },
        options
    );

    document.getElementById('title1').innerHTML = title1;
    document.getElementById('title2').innerHTML = title2;
    document.getElementById('title3').innerHTML = title3;
}

if (
    document.getElementById('price-chart') !== null &&
    document.getElementById('compare-chart') !== null &&
    document.getElementById('data-set') !== null
) {
    drawChart();
}
