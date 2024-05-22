<div id="chart_div" style="width: 100%; height: 500px; border:1px solid #ccc; margin-bottom: 25px;"></div>

<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>

<script type="text/javascript">
    google.charts.load('current', {
        packages: ['corechart']
    });

    google.charts.setOnLoadCallback(function(){ cartthrobChart(<?= $data ?>, '<?= $title ?>'); });

    function cartthrobChart(rows, chartTitle) {
        var data = new google.visualization.DataTable();

        data.addColumn("string", "");
        data.addColumn("number", "<?= lang('subtotal') ?>");
        data.addColumn("number", "<?= lang('tax') ?>");
        data.addColumn("number", "<?= lang('shipping') ?>");

        rows.forEach(function(row, i){
            index = data.addRow([
                String(row.date),
                Number(row.subtotal),
                Number(row.tax),
                Number(row.shipping)
            ]);

            data.setFormattedValue(index, 0, row.name);

            if (row.href != undefined) {
                data.setRowProperty(index, "href", row.href);
            }
        });

        var chart = new google.visualization.ColumnChart(document.getElementById("chart_div"));

        var chartOpts = {
            height: 500,
            width: $("#chart_div").width(),
            title: chartTitle,
            legend: { position: 'right' },
            pointSize: 7,
            isStacked: true
        };

        chart.draw(data, chartOpts);

        google.visualization.events.addListener(chart, "select", function(){
            selection = chart.getSelection();
            value = data.getRowProperty(selection[0].row, "href");

            console.log(selection);

            if (value != null) {
               window.location.href = (EE.BASE + '/addons/settings/cartthrob_order_manager').replace("?S=0", "?").replace(/(S=[\w\d]+)?&D=cp(.*?)$/, "$2&$1") + '&' + value;
            }
        });
    }
</script>