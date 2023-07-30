<?php

use yii\web\View;
use yii\helpers\Url;
use kartik\helpers\Html;

$this->title = "Dashboard";

$now = (new \DateTime())->format('Y-m-d');

$nextMaintenance = date('d M Y');
$countdowndata = "";
if($maintenance1)
{
  $nextMaintenance = date('d M Y', strtotime( $maintenance1 ));
  $date1 = new DateTime($nextMaintenance);
  $date2 = new DateTime($now);
  $countdowndata = "";
  if($date1 > $date2)
  {
    $interval = $date1->diff($date2);
    $countdowndata =  "";
  }
  else
  {
    $nextMaintenance = date('d M Y', strtotime( $now ));
    $countdowndata = ". <h4 style='color: red;'>Maintenance Date is Today.!</h4>";
  }
}

// var_dump($dataML);die;

?>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">Hai <?= Yii::$app->user->identity->user_nama ?></h3>
    </div>

    <div class="card-body">
        <p>Welcome to Predictive Maintenance System of Hemodialysis Reverse Osmosis Water Purification System (PMRO) </p>
    </div>

</div>

<div class="card card-primary">
    <div class="card-header">
        <h3 class="card-title">RO Data</h3>
    </div>

    <div class="card-body">
        <div class="row">

        <?php //var_dump(Yii::$app->user->identity) ?>
            <div class="col-2">
                <div class="mb-1">Select RO Device :</div>
                <input type="hidden" name="<?= Yii::$app->request->csrfParam; ?>" value="<?= Yii::$app->request->csrfToken; ?>" />
                <?= Html::dropDownList('node_name', $nodeId, $nodes, ['class' => 'form-control', 'id' => 'node_name', 'onchange'=>'changeDevice()'] //options
                    ) ?>
            </div>

            <div class="col-2">
                <div class="mb-1">Select Chart Type :</div>
                <select id="chart_type" name="chart_type" class="form-control" onchange="changeChartType();">
                    <option value="Line">Line</option>
                    <option value="Gauge">Gauge</option>
                </select>            
            </div>

            <div class="col-6">
                <div class="mb-1">Select Date : </div>
                <div class="row">
                    <div class="col-4">
                      <div class="form-group">
                          <div class="input-group date" id="start" data-target-input="nearest">
                          <input type="text" name="start" id="input_start" class="form-control datetimepicker-input" data-target="#start" />
                          <div class="input-group-append" data-target="#start" data-toggle="datetimepicker">
                              <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                          </div>
                          </div>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="form-group">
                          <button type="button" onclick="applyFilter()" class="ml-1 btn btn-primary form-control">Filter</button>
                      </div>
                    </div>
                    <div class="col-4">
                      <div class="form-group">
                          <button type="reset" onclick="resetFilter()" class="ml-1 btn btn-secondary form-control align-bottom">Reset</button>
                      </div>
                    </div>
                    <!-- <div class="col-6">
                    <div class="input-group date" id="end" data-target-input="nearest">
                        <input type="text" name="end" name="input_end" class="form-control datetimepicker-input" data-target="#end" />
                        <div class="input-group-append" data-target="#end" data-toggle="datetimepicker">
                        <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                        </div>
                    </div>
                    </div> -->
                </div>

            </div>

            <!-- <div class="col-4">
                <div class="mb-1">&nbsp;</div>
                <div class="row">
                    <div class="col-6">
                      <div class="form-group">
                          <button type="button" onclick="applyFilter()" class="ml-1 btn btn-primary form-control">Filter</button>
                      </div>
                    </div>
                    <div class="col-6">
                      <div class="form-group">
                          <button type="reset" onclick="resetFilter()" class="ml-1 btn btn-secondary form-control align-bottom">Reset</button>
                      </div>
                    </div>
                </div>
            </div> -->

        </div>

        <div class="row line">
            <div class="col-md-12">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Pressure</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="pressure-chart-wrapper" style="display:none">
                    <div class="chart">
                        <div id="pressure-chart" style="min-height: 150px;max-height: 300px;"></div>
                    </div>
                    </div>


                    <div id="pressure-loader">
                    <div class="skeleton-loader" style="height: 300px"></div>
                    <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

        <div class="row line">
            <div class="col-md-6">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Conductivity</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="con-chart-wrapper" style="display:none">
                    <div class="chart">
                        <div id="con-chart" style="min-height: 200px;max-height: 300px;"></div>
                    </div>
                    </div>


                    <div id="con-loader">
                    <div class="skeleton-loader" style="height: 300px"></div>
                    <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>

            <div class="col-md-6">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Flow Rate</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="flow-chart-wrapper" style="display:none">
                    <div class="chart">
                        <div id="flow-chart" style="min-height: 200px;max-height: 300px;"></div>
                    </div>
                    </div>


                    <div id="flow-loader">
                    <div class="skeleton-loader" style="height: 300px"></div>
                    <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>

        </div>

        <div class="row gauge" style="display:none">
            <div class="col-md-6">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Pressure</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="gauge-chart-wrapper" style="display:none">
                    <div class="chart">
                        <div id="gauge-chart" style="min-height: 200px;max-height: 300px;"></div>
                    </div>
                    </div>


                    <div id="gauge-loader">
                    <div class="skeleton-loader" style="height: 300px"></div>
                    <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>

            <div class="col-md-6">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Pressure</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="gauge-con-chart-wrapper" style="display:none">
                    <div class="chart">
                        <div id="gauge-con-chart" style="min-height: 200px;max-height: 300px;"></div>
                    </div>
                    </div>


                    <div id="gauge-con-loader">
                    <div class="skeleton-loader" style="height: 300px"></div>
                    <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>

            <div class="col-md-6">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Pressure</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="gauge-flow-chart-wrapper" style="display:none">
                    <div class="chart">
                        <div id="gauge-flow-chart" style="min-height: 200px;max-height: 300px;"></div>
                    </div>
                    </div>


                    <div id="gauge-flow-loader">
                    <div class="skeleton-loader" style="height: 300px"></div>
                    <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>


        <div class="row">
            <div class="col-md-12">
                <!-- AREA CHART -->
                <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Machine Learning Predictive Maintenance</h3>

                    <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse">
                        <i class="fas fa-minus"></i>
                    </button>

                    </div>
                </div>
                <div class="card-body">

                    <div id="ml-wrapper" style="display:none">
                        <div id="countdown1"></div><br>
                        <p><h4 style="text-align: center;" id="estimationText"><strong>Estimation for Device Failure = <span id="estimationValue"><?=$nextMaintenance.$countdowndata?></span></strong></h4></p>
                    </div>

                    <div id="ml-loader">
                        <div class="skeleton-loader" style="height: 300px"></div>
                        <div class="mt-3 skeleton-loader" style="height:20px"></div>
                    </div>

                </div>
                <!-- /.card-body -->
                </div>
                <!-- /.card -->
            </div>
        </div>

    </div>
</div>

<input type="hidden" id="anomaly" value='<?= json_encode( $Anomaly )?>'/>
<input type="hidden" id="trainingData"/>
<input type="hidden" id="lastData"/>
<input type="hidden" id="dayPrediction"/>

<script>
  'use strict';

  window.pressureChart = null;
  window.conChart = null;
  window.flowChart = null;
  window.gaugeChart = null;

  window.populationChart = null;

  let start = new Date('<?= $start ?>');
  let end = new Date('<?= $end ?>');

  window.applyFilter = async () => {
    dataPressure();

  };

  window.resetFilter = async () => {

    $('#start').datepicker({ dateFormat: 'YYYY-MM-DD'}).datepicker("setDate", start);

    dataPressure();
  };

  window.addEventListener('load', async (e) => {

    $('#start').datetimepicker({
      format: 'YYYY-MM-DD',
      defaultDate: start,
      disabledHours: [0, 1, 2, 3, 4, 5, 6, 20, 21, 22, 23, 24]
    });

    applyFilter();
      
  });


  window.dataPressure = async () => {

    $('#pressure-chart-wrapper').hide();
    $('#pressure-loader').show();

    $.post('<?= Url::to(['/dashboard/data-pressure']) ?>', {
      _csrf: $('#_csrf').attr('content'),
      device: document.getElementById("node_name").value,
      start: $('input[name="start"]').val()
      // ,end: $('input[name="end"]').val()
    }, (data) => {
      if(data.count<=0) {
        Swal.fire({
          icon: 'warning',
          html: '<h4>No data found!</h4>',
          timer:4000
        });
      }
      const options = {
        chart: {
          type: 'line',
          height: '400px'

        },
        series: [{
          name: 'Sensor 1 (Psi)',
          data: data.s1
        },
        {
          name: 'Sensor 2 (Psi)',
          data: data.s2
        },
        {
          name: 'Sensor 3 (Psi)',
          data: data.s3
        },
        {
          name: 'Sensor 4 (Psi)',
          data: data.s4
        },
        {
          name: 'Sensor 5 (Psi)',
          data: data.s5
        }],
        xaxis: {
          categories: data.date
        }
      }

      $('#pressure-loader').hide();
      $('#pressure-chart-wrapper').show();

      if (pressureChart && pressureChart.rendered) {
        pressureChart.destroy();
      }

      window.pressureChart = new ApexCharts(document.querySelector('#pressure-chart'), options);
      pressureChart.render().then(() => pressureChart.rendered = true);

      ////con chart

      const optionsCon = {
        chart: {
          type: 'line'
          ,height: '400px'
        },
        series: [{
          name: 'Sensor 1 (mS/cm)',
          data: data.s8
        },
        {
          name: 'Sensor 2 (mS/cm)',
          data: data.s9
        }],
        xaxis: {
          categories: data.date
        }
      }

      $('#con-loader').hide();
      $('#con-chart-wrapper').show();

      if (conChart && conChart.rendered) {
        conChart.destroy();
      }

      window.conChart = new ApexCharts(document.querySelector('#con-chart'), optionsCon);
      conChart.render().then(() => conChart.rendered = true);

       ////flow chart

       const optionsFlow = {
        chart: {
          type: 'line'
          ,height: '400px'
        },
        series: [{
          name: 'Sensor 1 (L/min)',
          data: data.s6
        },
        {
          name: 'Sensor 2 (L/min)',
          data: data.s7
        }],
        xaxis: {
          categories: data.date
        }
      }

      $('#flow-loader').hide();
      $('#flow-chart-wrapper').show();

      if (flowChart && flowChart.rendered) {
        flowChart.destroy();
      }

      window.flowChart = new ApexCharts(document.querySelector('#flow-chart'), optionsFlow);
      flowChart.render().then(() => flowChart.rendered = true);

        $('#ml-wrapper').show();
        $('#ml-loader').hide();

/////

var gauge_options = {
          series: [data.s1[data.s1.length-1], data.s2[data.s2.length-1], data.s3[data.s3.length-1], data.s4[data.s4.length-1],data.s5[data.s5.length-1]],
          chart: {
          height: 390,
          type: 'radialBar',
        },
        plotOptions: {
          radialBar: {
            offsetY: 0,
            startAngle: 0,
            endAngle: 270,
            hollow: {
              margin: 5,
              size: '30%',
              background: 'transparent',
              image: undefined,
            },
            dataLabels: {
              name: {
                show: false,
              },
              value: {
                show: false,
              }
            }
          }
        },
        labels: ['Sensor 1', 'Sensor 2', 'Sensor 3', 'Sensor 4', 'Sensor 5'],
        legend: {
          show: true,
          floating: true,
          fontSize: '16px',
          position: 'left',
          offsetX: 160,
          offsetY: 15,
          labels: {
            useSeriesColors: true,
          },
          markers: {
            size: 0
          },
          formatter: function(seriesName, opts) {
            return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex]
          },
          itemMargin: {
            vertical: 3
          }
        },
        responsive: [{
          breakpoint: 480,
          options: {
            legend: {
                show: false
            }
          }
        }]
        };

        $('#gauge-loader').hide();
        $('#gauge-chart-wrapper').show();
        var chart = new ApexCharts(document.querySelector("#gauge-chart"), gauge_options);
        chart.render();

        //gauge con

        var gauge_con_options = {
          series: [data.s8[data.s8.length-1], data.s9[data.s9.length-1]],
          chart: {
          height: 390,
          type: 'radialBar',
        },
        plotOptions: {
          radialBar: {
            offsetY: 0,
            startAngle: 0,
            endAngle: 270,
            hollow: {
              margin: 5,
              size: '30%',
              background: 'transparent',
              image: undefined,
            },
            dataLabels: {
              name: {
                show: false,
              },
              value: {
                show: false,
              }
            }
          }
        },
        labels: ['Sensor 1', 'Sensor 2'],
        legend: {
          show: true,
          floating: true,
          fontSize: '16px',
          position: 'left',
          offsetX: 160,
          offsetY: 15,
          labels: {
            useSeriesColors: true,
          },
          markers: {
            size: 0
          },
          formatter: function(seriesName, opts) {
            return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex]
          },
          itemMargin: {
            vertical: 3
          }
        },
        responsive: [{
          breakpoint: 480,
          options: {
            legend: {
                show: false
            }
          }
        }]
        };

        $('#gauge-con-loader').hide();
        $('#gauge-con-chart-wrapper').show();
        var chart = new ApexCharts(document.querySelector("#gauge-con-chart"), gauge_con_options);
        chart.render();

        //gauge flow

        var gauge_flow_options = {
          series: [data.s6[data.s6.length-1], data.s7[data.s7.length-1]],
          chart: {
          height: 390,
          type: 'radialBar',
        },
        plotOptions: {
          radialBar: {
            offsetY: 0,
            startAngle: 0,
            endAngle: 270,
            hollow: {
              margin: 5,
              size: '30%',
              background: 'transparent',
              image: undefined,
            },
            dataLabels: {
              name: {
                show: false,
              },
              value: {
                show: false,
              }
            }
          }
        },
        labels: ['Sensor 1', 'Sensor 2'],
        legend: {
          show: true,
          floating: true,
          fontSize: '16px',
          position: 'left',
          offsetX: 160,
          offsetY: 15,
          labels: {
            useSeriesColors: true,
          },
          markers: {
            size: 0
          },
          formatter: function(seriesName, opts) {
            return seriesName + ":  " + opts.w.globals.series[opts.seriesIndex]
          },
          itemMargin: {
            vertical: 3
          }
        },
        responsive: [{
          breakpoint: 480,
          options: {
            legend: {
                show: false
            }
          }
        }]
        };

        $('#gauge-flow-loader').hide();
        $('#gauge-flow-chart-wrapper').show();
        var chart = new ApexCharts(document.querySelector("#gauge-flow-chart"), gauge_flow_options);
        chart.render();

        window.dispatchEvent(new Event('resize'));


    });

  };

  function changeChartType() {
    if(document.getElementById("chart_type").value == "Line") {
        $('.line').show();
        $('.gauge').hide();
    } else {
        $('.line').hide();
        $('.gauge').show();
    }
  }

  function changeDevice() {
    applyFilter();
    changeChartType();
  }

const myTimeout = setTimeout(getDataML, 5000);

function formatDate(dateString) {
  // Create a new Date object from the input string
  var date = new Date(dateString);
  
  // Define the month names
  var monthNames = [
    "January", "February", "March", "April", "May", "June", 
    "July", "August", "September", "October", "November", "December"
  ];
  
  // Extract the day, month, and year from the Date object
  var day = date.getDate();
  var monthIndex = date.getMonth();
  var year = date.getFullYear();
  
  // Format the date string as "day MonthName year"
  var formattedDate = day + " " + monthNames[monthIndex] + " " + year;
  
  // Return the formatted date string
  return formattedDate;
}

function addDaysToEpoch(epoch, daysToAdd) {
  // Convert the epoch timestamp to milliseconds
  var milliseconds = epoch;
  
  // Create a new Date object from the epoch timestamp
  var date = new Date(milliseconds);
  
  // Add the specified number of days to the date
  date.setDate(date.getDate() + daysToAdd);
  
  // Convert the modified date back to an epoch timestamp
  var modifiedEpoch = Math.floor(date.getTime());
  
  // Return the modified epoch timestamp
  return modifiedEpoch;
}

function getDataML() {

  $.post('<?= Url::to(['/dashboard/data-predict']) ?>', {
      _csrf: $('#_csrf').attr('content'),
      device: document.getElementById("node_name").value
    }, (data) => {
      console.log("data",data);
      var i=0,n=0,z=0,x=0;
      let degradationValueTotal = 0;
      let degradationValue = 0.0001;
      let failureTimes = [];
      let forecast = [data.s1,data.s2,data.s3,data.s4,data.s5,data.s6,data.s7,data.s8,data.s9];
      let forecastN = forecast;
      let dayPrediction = 90;
      for (i; i < forecast.length; i++) 
      {   
        let n=0;
        while((forecastN[i]>=3 && forecastN[i]<=10))
        {
          if(forecastN[i] < 6.25)
          { //jika data trend keatas maka di tambahkan degradation
            forecastN[i] -= degradationValue;
          }
          else
          {
            forecastN[i] += degradationValue;
          }
          
          if(forecastN[i]<3 || forecastN[i]>10)
          {
            console.log("detected",forecastN[i],"in",n); 
            failureTimes[i] = n;
            z+=n;
          }
          if(++n>100000)break;
        }
        console.log("forecastN=",forecastN,"i=",i);
      }
      x = z/8;
      dayPrediction = x>0?parseInt((x*5)/60/24):90;
      console.log("z",z,"x",x);
      console.log("failureTimes=",failureTimes,"z=",z," rata2=",x);
      document.getElementById('dayPrediction').value = parseInt( dayPrediction );
      console.log("dayPrediction",document.getElementById('dayPrediction').value);

      
      if(dayPrediction > 0)
      {
        console.log("now",(Date.now()),"dayPrediction",dayPrediction);
        let modifiedEpoch = addDaysToEpoch((Date.now()), dayPrediction);
        console.log("modifiedEpoch",modifiedEpoch);
        let dateMaintenance = formatDate(modifiedEpoch);
        console.log("dateMaintenance",dateMaintenance);
        let dayText = dayPrediction>1?'Days':'Day';
        
        if(dayPrediction>7 && dayPrediction <=30) {
          document.getElementById('estimationValue').innerHTML = dateMaintenance + "<h4 style='color: yellow;'>Maintenance Date in "+dayPrediction+" "+dayText+"</h4>";
        } 
        else if(dayPrediction>0 && dayPrediction <=7) {
          document.getElementById('estimationValue').innerHTML = dateMaintenance + "<h4 style='color: orange;'>Maintenance Date in "+dayPrediction+" "+dayText+"</h4>";
        }
        else {
          document.getElementById('estimationValue').innerHTML = dateMaintenance + "<h4 style='color: green;'>Maintenance Date in "+dayPrediction+" "+dayText+"</h4>";
        }
      }
      else {
          document.getElementById('estimationValue').innerHTML = dateMaintenance + "<h4 style='color: red;'>Maintenance Date is Today!</h4>";
      }

    });
}

function addDays(date, days) {
  var result = new Date(date);
  result.setDate(result.getDate() + days);
  return result;
}

  //  //interval 60 sec to check 
  setInterval(function(){ 
     console.log("interval to show anomaly");
     if(document.getElementById("anomaly").value != "") {

        this.anomaly = JSON.parse(document.getElementById("anomaly").value);

        console.log(this.anomaly);
        if ( typeof this.anomaly === 'object' && !Array.isArray(this.anomaly) && this.anomaly !== null ) {
          
          console.log("anomaly detected");
          let detailsensor = "";
          let anomalyFlag = false;
          for (const [key, value] of Object.entries(this.anomaly)) {
                console.log(`${key}: ${value}`);
                detailsensor += "<h5 style='color: red;'>"+key+" = "+value+"</h5>";
                if(key) {
                  anomalyFlag = true;
                }
          }
          // let detailsensor = this.anomaly.forEach(getDetailSensor);
          if(anomalyFlag)
          {
              let infotext = 'System has detected anomaly data. <hr> '+detailsensor+' <hr> Please check the '+document.getElementById("node_name").value+' device.!';
              this.anomalyflag = false;

              //trial sent notif
              let dateMaintenance = new Date().toISOString().slice(0, 10);

              $.post('<?= Url::to(['/dashboard/create-notif']) ?>', {
                    _csrf: $('#_csrf').attr('content'),
                    notif_title:"Data Anomaly Report",
                    notif_text: "Data Anomaly Report: \nDevice : "+document.getElementById("node_name").value +'\nSystem has detected anomaly data. \n '+detailsensor+' \n Please check the '+document.getElementById("node_name").value+' device.!'
                  }, (data) => {
                                  Swal.fire({
                                  icon: 'success',
                                  html: '<h4>Notification will be sent ASAP.</h4>',
                                  timer:4000
                                });
                  });

                Swal.fire({
                          icon: 'warning',
                          title: 'Warning!',
                          text: infotext,
                          timer: 5000
                        });
                document.getElementById("anomaly").value = "";
          }
          
        }
     }

   }, 60000);
</script>