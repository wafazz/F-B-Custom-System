<?php

namespace App\Services\Reports;

use Carbon\CarbonImmutable;
use Illuminate\Support\Carbon;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Border;
use OpenSpout\Common\Entity\Style\BorderPart;
use OpenSpout\Common\Entity\Style\Color;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\StreamedResponse;

class SalesReportExporter
{
    public function __construct(protected SalesReportService $reports) {}

    public function stream(
        string $period,
        Carbon|CarbonImmutable $from,
        Carbon|CarbonImmutable $to,
        ?int $branchId,
        ?string $branchName,
    ): StreamedResponse {
        $filename = sprintf(
            'sales-report-%s-%s_to_%s.xlsx',
            $period,
            $from->format('Ymd'),
            $to->format('Ymd'),
        );

        return response()->streamDownload(function () use ($period, $from, $to, $branchId, $branchName) {
            $writer = new Writer();
            $writer->openToFile('php://output');

            $header = (new Style())
                ->setFontBold()
                ->setBackgroundColor(Color::rgb(243, 244, 246))
                ->setBorder(new Border(new BorderPart(Border::BOTTOM, Color::BLACK)));

            // Sheet 1 — Summary
            $writer->getCurrentSheet()->setName('Summary');
            $writer->addRow(Row::fromValues(['Star Coffee — Sales Report']));
            $writer->addRow(Row::fromValues(['Period', ucfirst($period)]));
            $writer->addRow(Row::fromValues(['From', $from->toDateTimeString()]));
            $writer->addRow(Row::fromValues(['To', $to->toDateTimeString()]));
            $writer->addRow(Row::fromValues(['Branch', $branchName ?? 'All branches']));
            $writer->addRow(Row::fromValues([]));

            $summary = $this->reports->summary($from, $to, $branchId);
            $writer->addRow(Row::fromValues(['Metric', 'Value'])->setStyle($header));
            $writer->addRow(Row::fromValues(['Paid orders', $summary['orders']]));
            $writer->addRow(Row::fromValues(['Total orders (incl. cancelled)', $summary['total_orders']]));
            $writer->addRow(Row::fromValues(['Cancelled', $summary['cancelled']]));
            $writer->addRow(Row::fromValues(['Refunded', $summary['refunded']]));
            $writer->addRow(Row::fromValues(['Gross subtotal (RM)', $summary['subtotal']]));
            $writer->addRow(Row::fromValues(['Discounts (RM)', $summary['discounts']]));
            $writer->addRow(Row::fromValues(['SST (RM)', $summary['sst']]));
            $writer->addRow(Row::fromValues(['Service charge (RM)', $summary['service_charge']]));
            $writer->addRow(Row::fromValues(['Revenue (RM)', $summary['revenue']]));
            $writer->addRow(Row::fromValues(['Average ticket (RM)', $summary['avg_ticket']]));

            // Sheet 2 — Per branch
            $writer->addNewSheetAndMakeItCurrent()->setName('By branch');
            $writer->addRow(Row::fromValues(['Branch', 'Orders', 'Revenue (RM)', 'Discounts (RM)'])->setStyle($header));
            foreach ($this->reports->byBranch($from, $to, $branchId) as $row) {
                $writer->addRow(Row::fromValues([
                    $row['branch_name'],
                    $row['orders'],
                    $row['revenue'],
                    $row['discounts'],
                ]));
            }

            // Sheet 3 — Top products
            $writer->addNewSheetAndMakeItCurrent()->setName('Top products');
            $writer->addRow(Row::fromValues(['Product', 'Quantity sold', 'Revenue (RM)'])->setStyle($header));
            foreach ($this->reports->topProducts($from, $to, $branchId, 50) as $row) {
                $writer->addRow(Row::fromValues([
                    $row['product_name'],
                    $row['quantity'],
                    $row['revenue'],
                ]));
            }

            // Sheet 4 — Daily series
            $writer->addNewSheetAndMakeItCurrent()->setName('Daily');
            $writer->addRow(Row::fromValues(['Date', 'Orders', 'Revenue (RM)'])->setStyle($header));
            foreach ($this->reports->timeSeries($from, $to, $branchId) as $row) {
                $writer->addRow(Row::fromValues([
                    $row['date'],
                    $row['orders'],
                    $row['revenue'],
                ]));
            }

            $writer->close();
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
