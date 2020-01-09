<?php
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

if ($receiptData) {
    $getTaxPercent = ($receiptData['txn_amount'] * 18) / 100;
    $amountAfterTax = $receiptData['txn_amount'] - $getTaxPercent;
    $paytm_response = json_decode($receiptData['paytm_response']);
    $paymentMode = $paytm_response->PAYMENTMODE == 'CC' ? 'Credit Card' : ($paytm_response->PAYMENTMODE == 'DC' ? 'Debit card' : ($paytm_response->PAYMENTMODE == 'NB' ? 'Net banking' : ($paytm_response->PAYMENTMODE == 'UPI' ? 'UPI' : ($paytm_response->PAYMENTMODE == 'PPI' ? 'Paytm wallet' : 'Postpaid'))));
    ?>
 
            <div class="pdf-main-bx clearfix" style="height: 100%;padding: 20px;">
                <div class="container">
                    <div class="inner-tbl clearfix" style="border-bottom: 1px solid #777;">
                        <h1 style="margin-bottom: 50px;font-weight: 700;text-decoration: underline;text-align: center;">Payment Receipt</h1>
                        <div class="detail-div">
                            <ul style="list-style: none;padding: 0;">
                                <li class="name-bx" style="width: 100%;float: left;">
                                    <div class="name-inner" style="width: 50%;float: left;font-weight: 500;padding: 7px 0 14px;"><b style="padding: 7px 0 0;display: inline-block;">Payment Reference No :-</b></div>
                                    <div class="name-inner" style="width: 50%;float: left;font-weight: 500;"><div class="name-bx" style="padding: 7px 0 0;display: inline-block;"><?php echo $paytm_response->TXNID ?></div></div>
                                </li>

                                <li class="name-bx" style="width: 100%;float: left;">
                                    <div class="name-inner" style="width: 50%;float: left;font-weight: 500;padding: 7px 0 14px;"><b style="padding: 7px 0 0;display: inline-block;">Patient Name :-</b></div>
                                    <div class="name-inner" style="width: 50%;float: left;font-weight: 500;"><div class="name-bx" style="padding: 7px 0 0;display: inline-block;"><?php echo $receiptData['user_name'] ?></div></div>
                                </li>

                                <li class="name-bx" style="width: 100%;float: left;">
                                    <div class="name-inner" style="width: 50%;float: left;padding: 7px 0 14px;"><b style="padding: 7px 0 0;display: inline-block;">Doctor Name :-</b></div>
                                    <div class="name-inner" style="width: 50%;float: left;font-weight: 500;"><div class="name-bx" style="padding: 7px 0 0;display: inline-block;"><?php echo $receiptData['doctor_name'] ?></div></div>
                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">

                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">
                                    <div class="name-inner"><b style="padding: 7px 0 14px;display: inline-block;">Appointment Date & Time :-</b></div>
                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">
                                    <div class="name-inner" style="width: auto;float: left;font-weight: 500; "><div class="time-bx" style="padding: 7px 0 14px;display: inline-block;"><?php echo date('d M, Y', strtotime($receiptData['date'])) ?> <?php echo date('h:i A', $receiptData['appointment_time']) ?></div></div>
                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">
                                    <div class="name-inner"><b style="padding: 7px 0 14px;display: inline-block;">Payment Date & Time :-</b></div>
                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">
                                    <div class="name-inner" style="width: auto;float: left;font-weight: 500; "><div class="time-bx" style="padding: 7px 0 14px;display: inline-block;"><?php echo date('d M, Y', strtotime($receiptData['originate_date'])) ?> <?php echo date('h:i A', strtotime($receiptData['originate_date'])) ?></div></div>
                                    
                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">
                                    <div class="name-inner"><b style="padding: 7px 0 14px;display: inline-block;">Mode of Payment :-</b></div>
                                </li>
                                <li class="name-bx" style="width: 50%;float: left;">
                                    <div class="name-inner" style="width: auto;float: left;font-weight: 500; "><div class="time-bx" style="padding: 7px 0 14px;display: inline-block;"><?php echo $paymentMode ?></div></div>
                                    
                                </li>

                            </ul>
                        </div>
                    </div>
                    <div class="tabl-bx">
                        <table style="border: 1px solid #777;float: right;width: 100%;">
                            <tbody>
                                <tr style="border-bottom: 1px solid #777;">
                                    <th style="border-right: 1px solid #777; padding: 2px 9px;text-align: left;border-bottom: 1px solid #777;">Service Charge </th>
                                    <td style="text-align: right;padding: 2px 9px;border-bottom: 1px solid #777;"><img src="http://demo.drspanel.in/images/rupee.png" style="height: 15px; width: 15px"><?php echo $amountAfterTax ?></td>
                                </tr>
                                <tr style="border-bottom: 1px solid #777;">
                                    <th style="border-right: 1px solid #777;  padding: 2px 9px;text-align: left;border-bottom: 1px solid #777;">TAX 18%  </th>
                                    <td style="text-align: right;padding: 2px 9px;border-bottom: 1px solid #777;"><img src="http://demo.drspanel.in/images/rupee.png" style="height: 15px; width: 15px"><?php echo $getTaxPercent ?></td>
                                </tr>
                                <tr>
                                    <th style="border-right: 1px solid #777; padding: 2px 9px;text-align: left;">Total </th>
                                    <td style="text-align: right; padding: 2px 9px; font-weight: bold"><img src="http://demo.drspanel.in/images/rupee.png" style="height: 15px; width: 15px"><?php echo $receiptData['txn_amount'] ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
       
<?php } ?>
