<?php
/**
 * Eugine Terentev <eugine@terentev.net>
 * @var $this \yii\web\View
 * @var $model \common\models\TimelineEvent
 * @var $dataProvider \yii\data\ActiveDataProvider
 */
$this->title = Yii::t('backend', 'Dashboard');
//$getAllAppoint = common\models\UserAppointmentLogs::find()->select(['YEAR(`date`) AS year, MONTH(`date`) AS month, COUNT(id) as app'])->where(['payment_status' => 'completed'])->andWhere('`date` >= "' . date('Y-01-01') . '" AND `date` <= "' . date('Y-m-d') . '" ')->groupBy(['YEAR(`date`), MONTH(`date`)'])->all();
//$query->select(['user.name AS author', 'post.title as title'])->from('user')

?>
<div class="box">
    <div class="box-body">
        <div class="meta-values-index">
            <div class="col-md-12">
                <div class="row">
                    <div class="col-lg-4 col-6">
                        <!-- small box -->
                        <div class="small-box bg-info">
                            <div class="inner">
                                <h3><?php echo $totalDoctor ?></h3>

                                <p>Doctor's</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                            <a href="<?php echo \yii\helpers\Url::to('@backendUrl').'/doctor/index'?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-success">
                            <div class="inner">
                                <h3><?php echo $totalHospital ?></h3>

                                <p>Hospital's</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                            <a href="<?php echo \yii\helpers\Url::to('@backendUrl').'/hospital/index'?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                    <div class="col-lg-4 col-6">
                        <div class="small-box bg-warning">
                            <div class="inner">
                                <h3><?php echo $totalPatient ?></h3>

                                <p>Patient</p>
                            </div>
                            <div class="icon">
                                <i class="ion ion-person-add"></i>
                            </div>
                            <a href="<?php echo \yii\helpers\Url::to('@backendUrl').'/patient/index'?>" class="small-box-footer">More info <i class="fa fa-arrow-circle-right"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-12">
                <div class="row">
                    <div class="col-md-8">
                        <div class="card-body">
                            <div class="d-flex">
                                <p class="d-flex flex-column">
                                    <span>Appointments Monthly</span>
                                </p>

                            </div>
                            <!-- /.d-flex -->

                            <div class="position-relative mb-4">
                                <canvas id="sales-chart" height="400"></canvas>
                            </div>

                            <div class="d-flex flex-row justify-content-end">
                                <span class="mr-2">
                                    <i class="fa fa-square text-primary"></i> Monthly
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card-body">
                            <div class="d-flex">
                                <p class="d-flex flex-column">
                                    <span>Appointments Per Day</span>
                                </p>
                            </div>
                            <div class="responsive-calendar calendar">
                                <div class="controls">
                                    <a class="pull-left" data-go="prev"><div class="btn btn-primary">Prev</div></a>
                                    <h4><span data-head-year></span> <span data-head-month></span></h4>
                                    <a class="pull-right" data-go="next"><div class="btn btn-primary">Next</div></a>
                                </div><hr/>
                                <div class="day-headers">
                                    <div class="day header">Mon</div>
                                    <div class="day header">Tue</div>
                                    <div class="day header">Wed</div>
                                    <div class="day header">Thu</div>
                                    <div class="day header">Fri</div>
                                    <div class="day header">Sat</div>
                                    <div class="day header">Sun</div>
                                </div>
                                <div class="days" data-group="days">

                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<?php
$currentDay = date('Y M');

$js = "
    $(function () {
  'use strict'

  var ticksStyle = {
    fontColor: '#495057',
    fontStyle: 'bold'
  }

  var mode      = 'index'
  var intersect = true

  var salesChart = $('#sales-chart')
  var salesChart  = new Chart(salesChart, {
    type   : 'bar',
    data   : {
      labels  : [$monthDataArr],
      datasets: [
        {
          backgroundColor: '#007bff',
          borderColor    : '#007bff',
          data           : [$mothApp]
        },
        
      ]
    },
    options: {
      maintainAspectRatio: false,
      tooltips           : {
        mode     : mode,
        intersect: intersect
      },
      hover              : {
        mode     : mode,
        intersect: intersect
      },
      legend             : {
        display: false
      },
      scales             : {
        yAxes: [{
          // display: false,
          gridLines: {
            display      : true,
            lineWidth    : '4px',
            color        : 'rgba(0, 0, 0, .2)',
            zeroLineColor: 'transparent'
          },
          ticks    : $.extend({
            beginAtZero: true,

            // Include a dollar sign in the ticks
            callback: function (value, index, values) {
              if (value >= 100) {
                value /= 100
                value += 'k'
              }
              return value
            }
          }, ticksStyle)
        }],
        xAxes: [{
          display  : true,
          gridLines: {
            display: false
          },
          ticks    : ticksStyle
        }]
      }
    }
  });
  });

";
$this->registerJs($js, \yii\web\VIEW::POS_END);
?>