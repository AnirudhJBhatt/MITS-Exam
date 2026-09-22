<?php
$content = file_get_contents('new_question-bank.php');

// Replace the Exam Search dropdown with the table
$searchBlockStart = '<div class="row g-3 mb-3">';
$searchBlockEnd = '</div>
                            </div>
                        </div>';

$tableHTML = <<<EOT
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col" style="width: 10%;">SL No</th>
                                        <th scope="col">Question Text</th>
                                        <th scope="col" style="width: 10%;">Type</th>
                                        <th scope="col" style="width: 10%;">Marks</th>
                                        <th scope="col" style="width: 10%;">CO</th>
                                        <th scope="col" style="width: 15%;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                        \$q=mysqli_query(\$con,"select * from new_question_bank where Fac_ID='\$Fac_ID' and (Course_ID='\$Course_ID' OR Course_ID IS NULL) ORDER BY Bank_ID DESC");
                                        \$sl_no = 1;
                                        if(mysqli_num_rows(\$q) > 0) {
                                            while(\$row=mysqli_fetch_array(\$q)){
                                                echo "<tr>";
                                                echo "<td>".\$sl_no."</td>";
                                                echo "<td>".htmlspecialchars(strip_tags(\$row['Question_Text']))."</td>";
                                                echo "<td><span class='badge bg-light text-dark border'>".strtoupper(\$row['Question_Type'])."</span></td>";
                                                echo "<td>".\$row['Marks']."</td>";
                                                echo "<td>CO".\$row['CO']."</td>";
                                                echo "<td><button class='btn btn-sm btn-primary d-inline-flex align-items-center gap-1' onclick='openEditBankModal(".\$row['Bank_ID'].")'><i class='ti ti-pencil'></i> Edit</button></td>";
                                                echo "</tr>";
                                                \$sl_no++;
                                            }
                                        } else {
                                            echo "<tr><td colspan='6' class='text-center text-muted'>No questions found.</td></tr>";
                                        }
                                    ?>
                                </tbody>
                            </table>
                        </div>
EOT;

$p1 = strpos($content, $searchBlockStart);
if ($p1 !== false) {
    // Find the end of this block
    $p2 = strpos($content, '</div>', $p1); // row
    $p2 = strpos($content, '</div>', $p2 + 6); // col
    $p2 = strpos($content, '</div>', $p2 + 6) + 6; // card-body end (approx)
    // To be safe, just replace between $searchBlockStart and `</div> </div> </div>` by looking for `<!-- Question Search Card -->`
    $b1 = strpos($content, '<div class="row g-3 mb-3">');
    $b2 = strpos($content, '</div>', strpos($content, '</select>')) + 6;
    $b2 = strpos($content, '</div>', $b2) + 6; // skip extra divs
    
    // Actually, I can use regex to replace it
    $content = preg_replace('/<div class="row g-3 mb-3">[\s\S]*?<\/select>\s*<\/div>\s*<\/div>/', $tableHTML, $content);
}

// Add the modal HTML
$modalHTML = <<<EOT
    <!-- Edit Bank Question Modal -->
    <div class="modal fade" id="editBankQuestionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Question</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="editBankQuestionModalBody">
                    <input type="hidden" id="editBankId">
                    <input type="hidden" id="editBankType">
                    
                    <div class="mb-3">
                        <label class="form-label small text-muted">Question Text</label>
                        <math-field id="editBankQuestionText" class="form-control" style="min-height: 80px;"></math-field>
                    </div>

                    <div class="row g-2 mb-3">
                        <div class="col-6">
                            <label class="form-label small text-muted">Marks</label>
                            <input type="number" id="editBankMarks" class="form-control form-control-sm" step="0.5" min="0.5">
                        </div>
                        <div class="col-6">
                            <label class="form-label small text-muted">CO</label>
                            <input type="number" id="editBankCO" class="form-control form-control-sm" min="1" max="6">
                        </div>
                    </div>
                    
                    <div id="editBankDynamicArea" class="border rounded p-3 bg-light">
                        <!-- Dynamic fields (options, etc.) go here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary btn-sm" onclick="submitEditBankQuestion()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>
EOT;

$content = str_replace('<!-- Question bank modal -->', $modalHTML . "\n\n    <!-- Question bank modal -->", $content);

// Add the JS
$jsCode = <<<EOT
        const editBankModal = new bootstrap.Modal(document.getElementById('editBankQuestionModal'));

        async function openEditBankModal(bankId) {
            try {
                const res = await fetch(`\${API}?action=get_bank_question&bank_id=\${bankId}`);
                const data = await res.json();
                if (data.success) {
                    const q = data.question;
                    document.getElementById('editBankId').value = q.Bank_ID;
                    document.getElementById('editBankType').value = q.Question_Type;
                    
                    const mf = document.getElementById('editBankQuestionText');
                    mf.value = q.Question_Text;
                    
                    document.getElementById('editBankMarks').value = q.Marks;
                    document.getElementById('editBankCO').value = q.CO;
                    
                    const dynamicArea = document.getElementById('editBankDynamicArea');
                    dynamicArea.innerHTML = '';
                    
                    if (q.Question_Type === 'mcq') {
                        let opts = q.Options_JSON ? JSON.parse(q.Options_JSON) : [];
                        let html = '<label class="form-label small text-muted">Options</label>';
                        ['A','B','C','D'].forEach((l, i) => {
                            let optText = opts[i] ? opts[i].text : '';
                            let isCorrect = q.Correct_Opt === l ? 'checked' : '';
                            html += `
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="radio" name="editBankCorrect" value="\${l}" class="form-check-input mt-0" \${isCorrect}>
                                <span class="badge bg-white text-dark border">\${l}</span>
                                <math-field id="editBankOpt_\${l}" class="flex-grow-1 form-control bg-white">\${optText}</math-field>
                            </div>`;
                        });
                        dynamicArea.innerHTML = html;
                    } else if (q.Question_Type === 'fill') {
                        let ans = q.Answers_JSON ? JSON.parse(q.Answers_JSON) : [];
                        let html = '<label class="form-label small text-muted">Answers (enter one per line)</label>';
                        html += `<textarea id="editBankFillAns" class="form-control form-control-sm" rows="3">\${ans.join('\\n')}</textarea>`;
                        dynamicArea.innerHTML = html;
                    } else if (q.Question_Type === 'match') {
                        let pairs = q.Pairs_JSON ? JSON.parse(q.Pairs_JSON) : [];
                        let html = '<div class="row g-2">';
                        html += '<div class="col-6"><label class="form-label small text-muted">Column A</label></div>';
                        html += '<div class="col-6"><label class="form-label small text-muted">Column B (Match)</label></div>';
                        for (let i=0; i<4; i++) {
                            let a = pairs[i] ? pairs[i].a : '';
                            let b = pairs[i] ? pairs[i].b : '';
                            html += `<div class="col-6"><math-field id="editBankMatchA_\${i}" class="form-control bg-white mb-1">\${a}</math-field></div>`;
                            html += `<div class="col-6"><math-field id="editBankMatchB_\${i}" class="form-control bg-white mb-1">\${b}</math-field></div>`;
                        }
                        html += '</div>';
                        dynamicArea.innerHTML = html;
                    } else if (q.Question_Type === 'open') {
                        let html = `<label class="form-label small text-muted">Rubric</label>`;
                        html += `<math-field id="editBankRubric" class="form-control bg-white mb-2">\${q.Rubric || ''}</math-field>`;
                        html += `<label class="form-label small text-muted">Word Limit</label>`;
                        html += `<input type="number" id="editBankWordLimit" class="form-control form-control-sm w-50" value="\${q.Word_Limit || ''}">`;
                        dynamicArea.innerHTML = html;
                    }
                    
                    editBankModal.show();
                } else {
                    showToast(data.message, 'danger');
                }
            } catch(err) {
                showToast('Failed to load question details.', 'danger');
            }
        }

        async function submitEditBankQuestion() {
            const type = document.getElementById('editBankType').value;
            const payload = {
                bank_id: document.getElementById('editBankId').value,
                type: type,
                question_text: document.getElementById('editBankQuestionText').value,
                marks: document.getElementById('editBankMarks').value,
                co: document.getElementById('editBankCO').value
            };
            
            if (type === 'mcq') {
                payload.options = [];
                ['A','B','C','D'].forEach(l => {
                    const mf = document.getElementById('editBankOpt_' + l);
                    if (mf) payload.options.push({ letter: l, text: mf.value });
                });
                const checked = document.querySelector('input[name="editBankCorrect"]:checked');
                if (checked) payload.correct_opt = checked.value;
            } else if (type === 'fill') {
                const text = document.getElementById('editBankFillAns').value;
                payload.answers = text.split('\\n').map(s => s.trim()).filter(s => s);
            } else if (type === 'match') {
                payload.pairs = [];
                for(let i=0; i<4; i++) {
                    const mfA = document.getElementById('editBankMatchA_' + i);
                    const mfB = document.getElementById('editBankMatchB_' + i);
                    if (mfA && mfB) payload.pairs.push({ a: mfA.value, b: mfB.value });
                }
            } else if (type === 'open') {
                payload.rubric = document.getElementById('editBankRubric').value;
                payload.word_limit = document.getElementById('editBankWordLimit').value;
            }
            
            try {
                const res = await fetch(`\${API}?action=update_bank_question`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                });
                const data = await res.json();
                if (data.success) {
                    editBankModal.hide();
                    showToast('Question updated successfully!');
                    setTimeout(() => location.reload(), 800);
                } else {
                    showToast(data.message, 'danger');
                }
            } catch(err) {
                showToast('Failed to update question.', 'danger');
            }
        }
EOT;

$content = str_replace("const qbankModal = new bootstrap.Modal(document.getElementById('qbankModal'));", $jsCode . "\n\n        const qbankModal = new bootstrap.Modal(document.getElementById('qbankModal'));", $content);

file_put_contents('new_question-bank.php', $content);
echo "new_question-bank.php updated successfully";
?>
