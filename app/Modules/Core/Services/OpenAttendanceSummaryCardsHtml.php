<?php

namespace App\Modules\Core\Services;

/**
 * Three statistic cards for the daily attendance summary email (table layout, inline styles).
 */
class OpenAttendanceSummaryCardsHtml
{
    public function render(int $checkInCount, int $checkOutCount, int $notCheckedOutCount): string
    {
        $brandPrimary = (string) config('brand.primary', '#14377d');
        $in = e((string) $checkInCount);
        $out = e((string) $checkOutCount);
        $open = e((string) $notCheckedOutCount);

        $cardBase = 'border-collapse:collapse;width:100%;border:1px solid #e4e4e7;border-radius:8px;background-color:#ffffff;';
        $labelStyle = 'margin:0 0 8px 0;font-size:13px;line-height:1.3;color:#71717a;';
        $valueStyle = 'margin:0;font-size:28px;line-height:1.2;font-weight:700;color:#18181b;';

        $cardStandardTop = 'border-top:3px solid '.$brandPrimary.';';
        $cardAlertTop = 'border-top:3px solid #dc2626;';
        $cardAlertBg = 'background-color:#fef2f2;';
        $labelAlert = 'margin:0 0 8px 0;font-size:13px;line-height:1.3;color:#dc2626;font-weight:600;';
        $valueAlert = 'margin:0;font-size:28px;line-height:1.2;font-weight:700;color:#dc2626;';

        $cellPad = 'padding:16px;';
        $gutter = 'width:33.33%;padding:0 6px 0 0;vertical-align:top;';
        $gutterMid = 'width:33.33%;padding:0 6px;vertical-align:top;';
        $gutterLast = 'width:33.33%;padding:0 0 0 6px;vertical-align:top;';

        $checkedInCard = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="'.$cardBase.$cardStandardTop.'">'
            .'<tr><td style="'.$cellPad.'">'
            .'<p style="'.$labelStyle.'">Checked in</p>'
            .'<p style="'.$valueStyle.'">'.$in.'</p>'
            .'</td></tr></table>';

        $checkedOutCard = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="'.$cardBase.$cardStandardTop.'">'
            .'<tr><td style="'.$cellPad.'">'
            .'<p style="'.$labelStyle.'">Checked out</p>'
            .'<p style="'.$valueStyle.'">'.$out.'</p>'
            .'</td></tr></table>';

        $notOutCard = '<table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="'.$cardBase.$cardAlertTop.$cardAlertBg.'">'
            .'<tr><td style="'.$cellPad.'">'
            .'<p style="'.$labelAlert.'">Not checked out</p>'
            .'<p style="'.$valueAlert.'">'.$open.'</p>'
            .'</td></tr></table>';

        return '<table role="presentation" width="100%" border="0" cellpadding="0" cellspacing="0" style="border-collapse:collapse;margin:0 0 24px 0;">'
            .'<tr>'
            .'<td style="'.$gutter.'">'.$checkedInCard.'</td>'
            .'<td style="'.$gutterMid.'">'.$checkedOutCard.'</td>'
            .'<td style="'.$gutterLast.'">'.$notOutCard.'</td>'
            .'</tr>'
            .'</table>';
    }
}
