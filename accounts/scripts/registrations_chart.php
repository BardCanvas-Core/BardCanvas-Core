<?php
/**
 * Dashboard widgets: Accounts / registrations chart
 *
 * @package    HNG2
 * @subpackage accounts
 * @author     Alejandro Caballero - lava.caballero@gmail.com
 * 
 * $_GET params:
 * @param number "width"
 * @param number "height"
 */

include "../../config.php";
include "../../includes/bootstrap.inc";
include "../../lib/phplot-6.1.0/rgb.inc.php";
include "../../lib/phplot-6.1.0/phplot.php";

$data  = array();
$query = "select date(creation_date) as creation_date, count(id_account) as total 
          from account 
          where date(creation_date) > '2015-01-01'
          group by date(creation_date)";
$res    = $database->query($query);

while($row = $database->fetch_object($res)) $data[$row->creation_date] = $row->total;

$current_date =
$first_date   = "2015-01-01";
$today        = date("Y-m-d");
$final_data   = array();
$prev_month   = "";
while( $current_date <= $today )
{
    $title = $current_date == $first_date || $current_date == $today || $data[$current_date] > 0
           ? date("Md", strtotime($current_date))
           : "";
    
    if( ! empty($title) )
    {
        if( $prev_month != date("M", strtotime($current_date)) )
            $title = date("Md", strtotime($current_date));
        else
            $title = date("d", strtotime($current_date));
        $prev_month = date("M", strtotime($current_date));
    }
    
    $final_data[] = array($title, $current_date, $data[$current_date]);
    $current_date = date("Y-m-d", strtotime("$current_date + 1 day"));
}
$data = $final_data;

$width  = empty($_REQUEST["width"])  ? 640 : $_REQUEST["width"];
$height = empty($_REQUEST["height"]) ? 480 : $_REQUEST["height"];

$plot = new PHPlot($width, $height);
# $plot->SetImageBorderType('plain');

$plot->SetPlotType('linepoints');
$plot->SetDataType('text-data');
$plot->SetNumberFormat(".", ";");
$plot->SetDataValues($data);
$plot->SetLineWidths(2);
$plot->SetDataColors("SkyBlue");

# Turn on Y data labels:
$plot->SetYDataLabelPos('plotin');

# With Y data labels, we don't need Y ticks or their labels, so turn them off.
$plot->SetYTickLabelPos('none');
$plot->SetYTickPos('none');

# Main plot title:
$plot->SetTitle(trim($current_module->language->widgets->registrations_chart->chart_title));

# Set Y data limits, tick increment, and titles:
# $plot->SetPlotAreaWorld(NULL, 0, NULL, NULL);
# $plot->SetYTickIncrement(10);
# $plot->SetYTitle(trim($current_module->language->widgets->registrations_chart->y_title));
# $plot->SetXTitle(trim($current_module->language->widgets->registrations_chart->x_title));

# Colors are significant to this data:
# $plot->SetDataColors(array('red', 'green', 'blue', 'yellow', 'cyan', 'magenta'));
$plot->SetMarginsPixels(null, null, null, null);
# $plot->SetLegendPixels(($width - 80), 25);
# $plot->SetLegend($leyendas);
# $plot->SetLegendUseShapes(true);

$plot->SetDrawXDataLabelLines(True);
$plot->SetDrawXGrid(false);
$plot->SetDrawYGrid(false);

# Turn off X tick labels and ticks because they don't apply here:
$plot->SetXTickLabelPos('none');
$plot->SetXTickPos('none');

$plot->DrawGraph();
