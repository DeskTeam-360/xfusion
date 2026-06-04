<?php
/**
 * Shortcode [gpi_radar_chart] — laporan GRIT individual (Gravity Forms + Chart.js).
 *
 * @package XFusion
 */

defined('ABSPATH') || exit;

/**
 * @param int $score
 */
function xfusion_grit_get_domain_insight($score): string
{
    if ($score >= 21) {
        return 'Thriving: You consistently show strengths in this area and are aligned with peak performance habits.';
    }
    if ($score >= 16) {
        return 'Progressing: You’re on the right path. Continued focus & refinement will build consistency & resilience.';
    }
    if ($score >= 11) {
        return 'Needs Support: There’s a solid foundation here. With greater awareness & effort, you can grow this domain.';
    }
    return 'Caution (at risk): This area will be limiting your progress. Now’s the time to realign with purpose and focus.';
}

/**
 * @return array{level: string, text: string}
 */
function xfusion_grit_get_domain_suggestion(string $domain, int $score): array
{
    if ($score >= 21) {
        $level = 'Thriving';
        $suggestions = [
            'Purpose'       => 'Keep reinforcing your purpose with new challenges to stay engaged.',
            'Focus'         => 'Sustain your focus by mentoring others on deep work strategies.',
            'Perseverance'  => 'Leverage your grit to tackle a long-term project and see it through.',
            'Energy'        => 'Maintain your energy balance—what habits help you recharge?',
            'Growth'        => 'Share your learning openly, it strengthens mastery.',
        ];
    } elseif ($score >= 16) {
        $level = 'Progressing';
        $suggestions = [
            'Purpose'       => 'Write out your purpose statement in 1 sentence. Revisit it weekly.',
            'Focus'         => 'Use a timer to create short, focused sprints (25 mins on, 5 off).',
            'Perseverance'  => 'Break big goals into micro-wins. Celebrate consistency over perfection.',
            'Energy'        => 'Try daily check-ins on physical, mental, and emotional energy.',
            'Growth'        => 'Seek feedback regularly, and apply it to strengthen skills.',
        ];
    } elseif ($score >= 11) {
        $level = 'Needs Support';
        $suggestions = [
            'Purpose'       => 'Reflect on what gives your work meaning. Start a purpose journal.',
            'Focus'         => 'Audit your top distractions and experiment with removing one.',
            'Perseverance'  => 'Find an accountability partner to help you stick to commitments.',
            'Energy'        => 'Prioritize rest—schedule downtime like any other task.',
            'Growth'        => 'Read or listen to one learning resource weekly in your domain.',
        ];
    } else {
        $level = 'Caution';
        $suggestions = [
            'Purpose'       => 'Revisit your core values and align at least one action daily with them.',
            'Focus'         => 'Eliminate multitasking. Practice focusing on one thing for 10 minutes.',
            'Perseverance'  => 'Start with very small goals. Build consistency before difficulty.',
            'Energy'        => 'Track your energy across the day. Identify drains and remove one.',
            'Growth'        => 'Adopt a beginner’s mindset—try something new every week.',
        ];
    }

    return [
        'level' => $level,
        'text'  => $suggestions[$domain] ?? '',
    ];
}

function gpi_radar_chart_shortcode() {
    $entry_id = isset($_GET['entry']) ? absint($_GET['entry']) : 0;
    $form_id = isset($_GET['form']) ? absint($_GET['form']) : 0;

    if (!$entry_id || !$form_id) return '<p>Result not found.</p>';
    if (!class_exists('GFAPI')) return '<p>Gravity Forms not active.</p>';

    $entry = GFAPI::get_entry($entry_id);
    if (is_wp_error($entry)) return '<p>Invalid entry.</p>';

    // Ambil nama dan tanggal submit dari entry
    $name = rgar($entry, '38'); // Ganti dengan field ID untuk nama
    $date_created = date_i18n('F j, Y', strtotime($entry['date_created']));
	// name fallback untuk activity yang menggunakan result page sama
	$name_for_activity = rgar($entry, '51'); // hidden field
	$form_id_current   = (int) $form_id;     // make sure it's int

	// define which form IDs are "activity forms"
	$activity_forms = [374, 467, 475]; // in future you can add more IDs here

	if (empty($name) && in_array($form_id_current, $activity_forms, true)) {
		$name = $name_for_activity;
	}

    // Ambil skor tiap domain (ganti ID sesuai field kamu)
    $purpose = (int) rgar($entry, '43');
    $focus = (int) rgar($entry, '44');
    $perseverance = (int) rgar($entry, '45');
    $energy = (int) rgar($entry, '46');
    $growth = (int) rgar($entry, '47');
    $total = $purpose + $focus + $perseverance + $energy + $growth;

    // Tier & deskripsi total skor
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

    ob_start();
    ?>
    <div class="grit-result-page">
		<style>
			header.elementor.elementor-20667.elementor-location-header,
			footer.elementor.elementor-20705.elementor-location-footer{
				display: none;
			}
			.elementor.elementor-18559 {
				margin: 0;
			}
			.grit-summary {
			  margin-bottom: 2rem;
			}
			.grit-summary hr {
				margin: 20px 0;
			}
			.grit-summary p {
			  margin: 0.6rem 0;
			  font-size: 20px; /* match site body size */
			  line-height: 1.5;
			  display: flex;
			  align-items: center;
			}
			.grit-summary p .dashicons {
			  font-size: 22px;
			  margin-right: 8px;
			  color: #87b14b;
			}
			.grit-summary p strong {
			  font-weight: 600;
			  margin-right: 4px;
			}
			.grit-summary .grit-suggestion {
			  font-size: 22px !important;
			  font-style: italic;
			  font-weight: 600;
			  margin-top: 4px;
			  padding: 8px 12px;
			  border-left: 3px solid #87b14b;
			  background: #f9f9f9;
			}
			.breakdown-domain-stacked {
			  margin-top: 1.5rem;
			}
			.breakdown-domain-stacked hr{
			    border: none;
				height: .5px;
				background-color: #ccc;
				margin: 20px 0;
			}
			.domain-item {
			  padding: 1rem;
			  margin-bottom: 1rem;
			}
			.domain-item h4 {
			  margin: 0 0 0.5rem;
			 font-size: 20px;
			 background: #e7efdb;
			 padding: 10px;
			 width: fit-content;
			 border-radius: 20px;
			 color: black;
			}
			.domain-item p {
			  margin: 0.3rem 0;
			}
			.btn-print, .btn-close-r {
				padding: 8px 14px; border: 1px solid #444; border-radius: 6px;
				background:#fff; cursor:pointer; font-weight:600;
			}
			.btn-print:hover, .btn-close-r:hover { background:#f5f5f5; }
		</style>
		<div class="grit-result-all-content grit-for-print">
		  <!-- PAGE 1 -->
		  <div class="print-page">
			<div class="grit-result-user-data-section">
			  <h2>Your Grit and Performance Summary Score</h2>
			  <div class="table-grit">
				<div class="grit-summary">
				  <p><span class="dashicons dashicons-admin-users"></span>
					<strong>Name:</strong> <?= esc_html($name) ?>
				  </p>
				  <p><span class="dashicons dashicons-calendar-alt"></span>
					<strong>Date:</strong> <?= esc_html($date_created) ?>
				  </p>
				  <p><span class="dashicons dashicons-chart-bar"></span>
					<strong>Total Score:</strong> <?= $total ?>/125
				  </p>
				  <p><span class="dashicons dashicons-awards"></span>
					<strong>Tier:</strong> <?= $tier ?>
				  </p>
				  <p>
				  	<span class="dashicons dashicons-welcome-write-blog"></span>
				  	<strong>Notes:</strong>
				 </p>
				 <p class="grit-suggestion">
					  <?= esc_html($desc) ?>
				 </p>
				</div>
			  </div>
			</div>
			<hr>
			<div class="grit-result-radar-section">
			  <h3>Grit & Purpose Profile (Radar)</h3>
			  <canvas id="gpiRadarChart" width="600" height="600"></canvas>
			</div>
		  </div>
			
		  <!-- PAGE 2 -->
		  <div class="print-page">
			  <hr>
			<div class="grit-result-domain-breakdown-section">
			  <h3>Domain Breakdown</h3>
			  <div class="breakdown-domain-stacked">
				<?php $purpose_suggestion = xfusion_grit_get_domain_suggestion("Purpose", $purpose); ?>
				<div class="domain-item">
				  <h4>🎯 Purpose: <?= esc_html($purpose) ?>/25</h4>
				  <p><em><?= xfusion_grit_get_domain_insight($purpose) ?></em></p>
				  <p><strong>Next Step:</strong> <?= esc_html($purpose_suggestion['text']) ?></p>
				</div>
				<hr>
				<?php $focus_suggestion = xfusion_grit_get_domain_suggestion("Focus", $focus); ?>
				<div class="domain-item">
				  <h4>⚡ Focus: <?= esc_html($focus) ?>/25</h4>
				  <p><em><?= xfusion_grit_get_domain_insight($focus) ?></em></p>
				  <p><strong>Try This:</strong> <?= esc_html($focus_suggestion['text']) ?></p>
				</div>
				<hr>
				<?php $perseverance_suggestion = xfusion_grit_get_domain_suggestion("Perseverance", $perseverance); ?>
				<div class="domain-item">
				  <h4>🛠️ Perseverance: <?= esc_html($perseverance) ?>/25</h4>
				  <p><em><?= xfusion_grit_get_domain_insight($perseverance) ?></em></p>
				  <p><strong>Build Resilience:</strong> <?= esc_html($perseverance_suggestion['text']) ?></p>
				</div>
				<hr>
				<?php $energy_suggestion = xfusion_grit_get_domain_suggestion("Energy", $energy); ?>
				<div class="domain-item">
				  <h4>🔋 Energy Management: <?= esc_html($energy) ?>/25</h4>
				  <p><em><?= xfusion_grit_get_domain_insight($energy) ?></em></p>
				  <p><strong>Boost Capacity:</strong> <?= esc_html($energy_suggestion['text']) ?></p>
				</div>
				<hr>
				<?php $growth_suggestion = xfusion_grit_get_domain_suggestion("Growth", $growth); ?>
				<div class="domain-item">
				  <h4>📈 Growth Mindset: <?= esc_html($growth) ?>/25</h4>
				  <p><em><?= xfusion_grit_get_domain_insight($growth) ?></em></p>
				  <p><strong>Lean In:</strong> <?= esc_html($growth_suggestion['text']) ?></p>
				</div>
			  </div>
			</div>
		  </div>
			
		  <!-- PAGE 3 -->
		  <div class="print-page">
			  <hr>
			<div class="grit-result-actionable-step-section">
			  <h3>Actionable Next Steps</h3>
			  <div class="suggest-grit">
				<h4>Suggested Reflections:</h4>
				<ul>
				  <li>“Where in my life do I need more grit?”</li>
				  <li>“How can I align my actions more with purpose?”</li>
				  <li>“What’s one routine I can adjust to better manage my energy?”</li>
				</ul>
			  </div>
			  <div class="suggest-grit">
				<h4>Optional Add-ons:</h4>
				<ul>
				  <li>Custom development plans</li>
				  <li>Journaling prompts</li>
				  <li>Related workshop or coaching session links</li>
				</ul>
			  </div>
			</div>
		  </div>

		</div>
		<hr/>
		<div style="display: flex; justify-content:center; align-items: center; gap:2rem; margin: 20px 0;">
			<button id="printReportBtn" class="btn-print">Email me the result</button>
			<button id="closeTabBtn" class="btn-close-r">Close Tab</button>
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
		new Chart(document.getElementById("gpiRadarChart"), {
		  type: 'radar',
		  data: {
			labels: ['Purpose', 'Focus', 'Perseverance', 'Energy', 'Growth'],
			datasets: [{
			  label: 'Your GPI Profile',
			  data: [<?= $purpose ?>, <?= $focus ?>, <?= $perseverance ?>, <?= $energy ?>, <?= $growth ?>],
			  fill: true,
			  backgroundColor: 'rgba(135, 177, 75, 0.2)',
			  borderColor: 'rgba(135, 177, 75, 1)',
			  pointBackgroundColor: 'rgba(135, 177, 75, 1)',
			  pointBorderColor: '#fff'
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
				}
			  }
			},
			plugins: {
			  legend: { display: false },
			  datalabels: {
				color: '#555', // lighter gray
				font: {
				  size: 12,
				  weight: '600'
				},
				formatter: function(value) {
				  return value; // shows actual score
				},
				align: 'top',
				offset: 3
			  }
			}
		  },
		  plugins: [ChartDataLabels] // register plugin
		});
    });
    </script>
	<!--  Print Plugins and function	 -->
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
		a.download = "GPI_report.pdf";
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
		document.getElementById('closeTabBtn').addEventListener('click', function () {
        // Try to close the tab
        window.close();

        // Some browsers block closing tabs not opened by script
        // Show message if it fails
        setTimeout(() => {
            if (!window.closed) {
                alert("Because this page was opened directly or from a link, the browser won’t let it close automatically. Please close this tab yourself.");
            }
        }, 200);
    });
	})();
	</script>

    <?php
    return ob_get_clean();
}
add_shortcode('gpi_radar_chart', 'gpi_radar_chart_shortcode');