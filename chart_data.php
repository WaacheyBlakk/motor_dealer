<?php
include 'db.php';

// DAILY SALES (last 7 days)
$dailyData = [];
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $query = $conn->prepare("SELECT IFNULL(SUM(amount),0) as total FROM sales WHERE sale_date=?");
    $query->bind_param("s", $date);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    $dailyData[] = ["date" => $date, "total" => (float)$result['total']];
}

// MONTHLY SALES (current year)
$currentYear = date('Y');
$monthlyData = [];
for ($m = 1; $m <= 12; $m++) {
    $query = $conn->prepare("SELECT IFNULL(SUM(amount),0) as total FROM sales WHERE YEAR(sale_date)=? AND MONTH(sale_date)=?");
    $query->bind_param("ii", $currentYear, $m);
    $query->execute();
    $result = $query->get_result()->fetch_assoc();
    $monthlyData[] = ["month" => date("M", mktime(0,0,0,$m,10)), "total" => (float)$result['total']];
}

// Return JSON
echo json_encode([
    "daily" => $dailyData,
    "monthly" => $monthlyData
]);
?>
<script>
fetch('includes/chart_data.php')
  .then(response => response.json())
  .then(data => {
    // DAILY CHART
    const dailyCtx = document.getElementById('dailySalesChart');
    new Chart(dailyCtx, {
      type: 'line',
      data: {
        labels: data.daily.map(item => item.date),
        datasets: [{
          label: 'Daily Sales (₵)',
          data: data.daily.map(item => item.total),
          borderColor: 'blue',
          fill: false,
          tension: 0.3
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true, position: 'top' },
          title: { display: true, text: 'Daily Sales (Last 7 Days)' }
        }
      }
    });

    // MONTHLY CHART
    const monthlyCtx = document.getElementById('monthlySalesChart');
    new Chart(monthlyCtx, {
      type: 'bar',
      data: {
        labels: data.monthly.map(item => item.month),
        datasets: [{
          label: 'Monthly Sales (₵)',
          data: data.monthly.map(item => item.total),
          backgroundColor: 'rgba(75, 192, 192, 0.6)'
        }]
      },
      options: {
        responsive: true,
        plugins: {
          legend: { display: true, position: 'top' },
          title: { display: true, text: 'Monthly Sales (Current Year)' }
        },
        scales: {
          y: { beginAtZero: true }
        }
      }
    });
  });
</script>

