<?php
/**
 * Shortcode [gpi_radar_chart_team] — laporan GRIT untuk tim / kandidat.
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

function xfusion_grit_team_domain_status(int $score): string
{
    if ($score >= 21) {
        return 'Strength';
    }
    if ($score >= 16) {
        return 'Steady';
    }
    return 'Caution';
}

function xfusion_grit_team_development_flag(int $score): string
{
    if ($score < 16) {
        return '✅';
    }
    if ($score <= 20) {
        return 'Maybe';
    }
    return '❌';
}

function gpi_radar_chart_team_shortcode() {
    $entry_id = isset($_GET['entry']) ? absint($_GET['entry']) : 0;
    $form_id = isset($_GET['form']) ? absint($_GET['form']) : 0;

    if (!$entry_id || !$form_id) return '<p>Result not found.</p>';
    if (!class_exists('GFAPI')) return '<p>Gravity Forms not active.</p>';

    $entry = GFAPI::get_entry($entry_id);
    if (is_wp_error($entry)) return '<p>Invalid entry.</p>';

    $name = rgar($entry, '38'); // Nama
    $date_created = date_i18n('F j, Y', strtotime($entry['date_created']));
	// name fallback untuk activity yang menggunakan result page sama
	$name_for_activity = rgar($entry, '51'); // hidden field
	$form_id_current   = (int) $form_id;     // make sure it's int

	// define which form IDs are "activity forms"
	$activity_forms = [374, 467, 475]; // in future you can add more IDs here

	if (empty($name) && in_array($form_id_current, $activity_forms, true)) {
		$name = $name_for_activity;
	}

    // Skor per domain
    $purpose = (int) rgar($entry, '43');
    $focus = (int) rgar($entry, '44');
    $perseverance = (int) rgar($entry, '45');
    $energy = (int) rgar($entry, '46');
    $growth = (int) rgar($entry, '47');

    $total = $purpose + $focus + $perseverance + $energy + $growth;

    // Tier interpretasi
    if ($total >= 115) {
        $tier = "Elite Grit";
        $desc = "Strong purpose, discipline, and drive across dimensions. Likely to excel in long-term challenges.";
    } elseif ($total >= 100) {
        $tier = "High Grit";
        $desc = "Strong internal resources. May benefit from refining digital boundaries or energy pacing.";
    } elseif ($total >= 85) {
        $tier = "Moderate Grit";
        $desc = "Solid foundations, but growth opportunities in focus, stamina, or intentionality.";
    } else {
        $tier = "Emerging Grit";
        $desc = "Needs support developing focus, resilience, and values-alignment.";
    }

    $domains = [
        ["Purpose", $purpose],
        ["Focus", $focus],
        ["Perseverance", $perseverance],
        ["Energy Management", $energy],
        ["Growth & Mastery", $growth],
    ];

    ob_start();
    ?>
	<style>
		header.elementor.elementor-20667.elementor-location-header,
		footer.elementor.elementor-20705.elementor-location-footer{
			display: none;
		}
		.elementor.elementor-18626 {
			margin: 0;
		}
		.grit-information p {
		  margin: 6px 0;
		  font-size: 20px;
		}

		.grit-information strong {
		  font-weight: 600;
		}

		.grit-information .dashicons {
			font-size: 22px;
		  margin-right: 6px;
		  color: #87b14b; /* match your accent */
		}

		.grit-suggestion {
		  font-size: 22px !important;
		  font-style: italic;
		  font-weight: 600;
		  margin-top: 4px;
		  padding: 8px 12px;
		  border-left: 3px solid #87b14b;
		  background: #f9f9f9;
		}
		.grit-result-page hr {
			margin: 30px 0;
		}
		.btn-print {
			padding: 8px 14px; border: 1px solid #444; border-radius: 6px;
			background:#fff; cursor:pointer; font-weight:600;
		}
		.btn-print:hover { background:#f5f5f5; }
	</style>
	<div class="grit-result-page">
		<div class="grit-result-all-content grit-for-print">
		  <!-- PAGE 1 -->
		  <div class="print-page">
			  <h2>Candidate / Team Member Grit & Purpose Report</h2>
			  <div class="grit-information">
				<p>
				  <span class="dashicons dashicons-admin-users"></span>
				  <strong>Candidate/Employee Name:</strong>
				  <?= esc_html($name) ?>
				</p>
				<p>
				  <span class="dashicons dashicons-calendar-alt"></span>
				  <strong>Date:</strong>
				  <?= esc_html($date_created) ?>
				</p>
				<p>
				  <span class="dashicons dashicons-chart-bar"></span>
				  <strong>Total Score:</strong>
				  <?= $total ?>/125
				</p>
				<p>
				  <span class="dashicons dashicons-awards"></span>
				  <strong>Overall Grit Tier:</strong>
				  <?= $tier ?>
				</p>
				<p>
				  <span class="dashicons dashicons-welcome-write-blog"></span>
				  <strong>Fit Notes:</strong>
				</p>
				<p class="grit-suggestion">
				  <?= esc_html($desc) ?>
				</p>
			  </div>
			  <hr>
			  <h3>Grit & Purpose Profile (Radar)</h3>
			  <canvas id="gpiRadarChart" width="600" height="600"></canvas>
		</div>

		  <!-- PAGE 2 -->
		  <div class="print-page">
			  <hr>
			<h3>Domain Analysis</h3>
			<div class="table-domain">
			  <table border="1" cellpadding="5" cellspacing="0" style="width:100%; text-align:center; border-collapse:collapse;">
				<thead style="background:#f5f5f5;">
				  <tr>
					<th>Domain</th>
					<th>Score</th>
					<th>Strength / Caution</th>
					<th>Development Flag</th>
				  </tr>
				</thead>
				<tbody>
				  <?php foreach ($domains as [$label, $score]) : ?>
					<tr>
					  <td><?= $label ?></td>
					  <td><?= $score ?></td>
					  <td><?= xfusion_grit_team_domain_status($score) ?></td>
					  <td><?= xfusion_grit_team_development_flag($score) ?></td>
					</tr>
				  <?php endforeach; ?>
				</tbody>
			  </table>
			</div>
		  </div>

		  <!-- PAGE 3 -->
		  <div class="print-page">
			  <hr>
			<h3>Interview / Coaching Guide</h3>
			<div class="suggest-grit">
			  <h4>Suggested Questions:</h4>
			  <ul>
				<li>“Tell me about a time you had to stay focused during a personally distracting period.”</li>
				<li>“How do you recharge when you're mentally or emotionally drained?”</li>
				<li>“How do your daily actions reflect something meaningful to you?”</li>
			  </ul>
			</div>
			<div class="suggest-grit">
			  <h4>Interpretation Notes:</h4>
			  <ul>
				<li>Low Focus + high Purpose → May need structured environments</li>
				<li>High Growth + low Perseverance → Coach for stamina, long-game mindset</li>
				<li>Broad high scores → Great candidate for leadership development</li>
			  </ul>
			</div>
		  </div>
		</div>
		<hr/>
		<div style="display: flex; justify-content:center; align-items: center; margin: 20px 0;">
			<button id="printReportBtn" class="btn-print">Email me the result</button>
		</div>
		<div id="pdf-loader" style="
		  display:none !important;
		  position:fixed;
		  top:0;left:0;right:0;bottom:0;
		  background:rgba(255,255,255,0.7);
		  z-index:9999;
		  display:flex;
		  align-items:center;
		  justify-content:center;
		  font-size:20px;
		  font-weight:bold;
		  color:#333;
		">
		  <div class="spinner" style="
			border:6px solid #f3f3f3;
			border-top:6px solid #87b14b;
			border-radius:50%;
			width:40px;
			height:40px;
			animation:spin 1s linear infinite;
			margin-right:12px;
		  "></div>
		  Generating your PDF...
		</div>
		<style>
		@keyframes spin {
		  0% { transform:rotate(0deg); }
		  100% { transform:rotate(360deg); }
		}
		</style>
	</div>
	
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
	<!-- ChartJS plugins and script to load -->
	<script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels"></script> 
    <script>
    document.addEventListener("DOMContentLoaded", function () {
        const scores = [<?= $purpose ?>, <?= $focus ?>, <?= $perseverance ?>, <?= $energy ?>, <?= $growth ?>];
        const colors = scores.map(score => {
            if (score >= 21) return 'rgba(75, 192, 192, 0.6)';      // green
            if (score >= 15) return 'rgba(255, 206, 86, 0.6)';      // yellow
            return 'rgba(255, 99, 132, 0.6)';                       // red
        });

        new Chart(document.getElementById("gpiRadarChart"), {
            type: 'radar',
            data: {
                labels: ['Purpose', 'Focus', 'Perseverance', 'Energy', 'Growth'],
                datasets: [{
                    label: 'Your GPI Profile',
                    data: scores,
                    fill: true,
                    backgroundColor: 'rgba(135, 177, 75, 0.2)',
                    borderColor: 'rgba(135, 177, 75, 1)',
                    pointBackgroundColor: colors,
                    pointBorderColor: '#fff',
                    pointHoverBackgroundColor: '#fff',
                    pointHoverBorderColor: colors
                }]
            },
            options: {
                scales: {
					r: {
						min: 5,
						max: 25,
						ticks: {
						  stepSize: 5,
						  callback: function(value) {
							return (value >= 15) ? value : '';
						  }
						},
						pointLabels: {
						  font: {
							size: 14,   // increase font size (default ~12)
							weight: 600
						  },
						  color: '#222', // darker for better contrast
						  padding: 10
						},
						angleLines: { color: '#ccc' },
                    	grid: { color: '#eee' }
					  }
                },
                plugins: {
                    legend: { display: false },
					datalabels: {
						color: '#555',
						align: 'end',
          				offset: 8,
						font: {
							weight: '600',
							size: 12
						},
						formatter: (value, context) => {
							return value; // show actual score on each point
						}
					},
					align: 'top',
					offset: 3
                }
            },
			plugins: [ChartDataLabels]
        });
    });
    </script>
	<!--  Print Plugins and function -->
	<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
	<script>
	  window.userID = <?php echo get_current_user_id(); ?>;
	  window.userEmail = "<?php 
		$current_user = wp_get_current_user(); 
		echo esc_js( $current_user->user_email ); 
	  ?>";
	  console.log("WP user email:", window.userEmail); // debug
	</script>
	<script>
	(async function () {
	  const { jsPDF } = window.jspdf;

	  async function postReportPDF(source) {
	  const loader = document.getElementById("pdf-loader");
	  loader.style.display = "flex"; // SHOW loader

	  try {
		const pdf = new jsPDF('p', 'pt', 'a4');
		const pageWidth  = pdf.internal.pageSize.getWidth();
		const pageHeight = pdf.internal.pageSize.getHeight();
		const margin = 20;

		const pages = source.querySelectorAll(".print-page");
		let firstPage = true;

		for (let page of pages) {
		  const canvas = await html2canvas(page, { scale: 2 });
		  const imgData = canvas.toDataURL("image/jpeg", 0.8);

		  const availableWidth  = pageWidth - margin * 2;
		  const availableHeight = pageHeight - margin * 2;

		  let imgWidth = availableWidth;
		  let imgHeight = canvas.height * (imgWidth / canvas.width);

		  if (imgHeight > availableHeight) {
			imgHeight = availableHeight;
			imgWidth = canvas.width * (imgHeight / canvas.height);
		  }

		  if (!firstPage) pdf.addPage();
		  pdf.addImage(imgData, "JPEG", margin, margin, imgWidth, imgHeight);
		  firstPage = false;
		}

		const pdfBlob = pdf.output("blob");

		// auto download
		const url = URL.createObjectURL(pdfBlob);
		const a = document.createElement("a");
		a.href = url;
		a.download = "GPI_team_report.pdf";
		a.click();
		URL.revokeObjectURL(url);

		// upload
		const formData = new FormData();
		formData.append("user_id", window.userID);
		formData.append("pdf_result", pdfBlob, "report.pdf");

		const origin = window.location.origin;
		let apiBase = '';
		if (origin.includes('sandbox.xperiencefusion.com')) {
		  apiBase = 'https://admin.sandbox.xperiencefusion.com';
		} else {
		  apiBase = 'https://admin.xperiencefusion.com';
		}

		await fetch(`${apiBase}/api/save-pdf-result`, {
		  method: "POST",
		  body: formData
		});

		alert("Email has been sent to " + window.userEmail);

	  } catch (err) {
		console.error("Upload failed:", err);
		alert("Failed to send report. Please try again.");
	  } finally {
		loader.style.display = "none"; // HIDE loader after everything
	  }
	  }

	  document.getElementById("printReportBtn").addEventListener("click", () => {
		const source = document.querySelector(".grit-for-print");
		if (!source) return alert("Element not found!");
		postReportPDF(source);
	  });
	})();
	</script>

    <?php
    return ob_get_clean();
}
add_shortcode('gpi_radar_chart_team', 'gpi_radar_chart_team_shortcode');