<?php
require 'db.php';
session_start();

$user_id = $_SESSION['login_id'] ?? 1;

// ✅ Handle delete request if made (integrated logic from delete_history.php)
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['created_at'])) {
    $created_at = $_POST['created_at'];

    $stmt = $conn->prepare("DELETE FROM prompt_history WHERE user_id = ? AND created_at = ?");
    $stmt->bind_param("is", $user_id, $created_at);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    exit(); // Exit early to avoid sending HTML in fetch call
}

// ✅ Handle storing new prompt-response
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['prompt'], $_POST['response'])) {
    $prompt = trim($_POST['prompt']);
    $response = trim($_POST['response']);
    $model_used = $_POST['model'] ?? 'T5';  // ✅ Get the model from POST data

    $stmt = $conn->prepare("INSERT INTO prompt_history (user_id, prompt, response, model_used) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $prompt, $response, $model_used);
    $stmt->execute();
    $stmt->close();
    $conn->close();
    exit(); // Exit to prevent sending HTML
}


// ✅ Fetch prompt history
$stmt = $conn->prepare("SELECT prompt, response, created_at FROM prompt_history WHERE user_id = ? ORDER BY created_at ASC");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$history = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <title>Prompt Page</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
    <style>
        html,
        body {
            height: 100%;
            margin: 0;
            overflow: hidden;
        }

        #sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            /* Full height */
            min-width: 200px;
            max-width: 200px;
            background-color: rgb(24, 48, 71);
            padding-top: 20px;
            overflow-y: auto;
            /* Allow scroll if content exceeds height */
        }


        #sidebar a {
            color: white;
            display: block;
            padding: 10px;
            text-decoration: none;
            font-weight: bold;

        }

        #sidebar a:hover {
            background-color: #495057;
        }


        .chat-wrapper {
            display: flex;
            flex-direction: column;
            height: 100%;
        }

        .chat-header {
            padding: 10px 20px;
            background: #f8f9fa;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid #ccc;
            position: relative;
        }

        .chat-body {
            flex-grow: 1;
            overflow-y: auto;
            padding: 20px;
            background-color: #ffffff;
        }

        .chat-footer {
            padding: 10px 20px;
            background: #f1f1f1;
            border-top: 1px solid #ccc;
            display: flex;
            align-items: center;
        }

        .chat-footer form {
            display: flex;
            width: 100%;
            gap: 10px;
        }

        .chat-footer input[type="text"] {
            flex-grow: 1;
            padding: 10px 20px;
            border: 1px solid #ccc;
            border-radius: 25px;
            font-size: 1rem;
        }

        .chat-footer input:focus {
            outline: none;
            border-color: #4CAF50;
        }

        .chat-footer button {
            border-radius: 25px;
            padding: 10px 25px;
            border: none;
            background-color: #457247ff;
            color: white;
            font-weight: 600;
            cursor: pointer;
        }

        .chat-footer button:hover {
            background-color: rgb(9, 58, 11);
        }

        .chat-msg {
            margin-bottom: 15px;
        }

        .chat-prompt {
            font-weight: bold;
        }

        .chat-response {
            margin-left: 10px;
        }

        #historyBox {
            display: none;
            position: absolute;
            top: 60px;
            right: 20px;
            width: 300px;
            max-height: 300px;
            overflow-y: auto;
            background: #fff;
            border: 1px solid #ccc;
            padding: 10px;
            z-index: 1000;
        }
    </style>
</head>

<body>

    <div id="sidebar" class="d-flex flex-column justify-content-between">
        <!-- Top section: Navigation -->
        <div>
            <a href="select_table.php">DB_CREATOR</a>
            <a href="prompt.php">PROMPT</a>
            <a href="verify.php">VERIFY</a>
            <a href="logout.php">LOGOUT</a>
        </div>

    </div>

    <div class="chat-wrapper" style="margin-left: 200px;">
        <!-- Header -->
        <div class="chat-header">
            <div class="ms-auto d-flex align-items-center">
                <h5 class="m-0 fw-bold">Query Checker</h5>
            </div>
            <div class="ms-auto d-flex align-items-center">
                <!-- <button class="btn btn-secondary me-2" onclick="toggleHistory()">History</button> -->
                <form action="verify.php" method="POST" class="me-2" id="verifyForm">
                    <input type="hidden" name="prompt" id="verifyPrompt" />
                    <input type="hidden" name="response" id="verifyResponse" />

                </form>

            </div>

            <!-- Bottom section: Model Selector -->
            <?php
            $model = $_POST['model'] ?? 'T5'; // Set model safely at top of file
            ?>

            <form method="POST" action="" style="padding: 10px; display: flex; align-items: center; gap: 8px;">
                <label for="model" style="color: black; font-weight: bold; margin: 0;">Model:</label>
                <select name="model" id="model" class="form-select form-select-sm" style="width: 110px;" onchange="this.form.submit()">
                    <option value="T5" <?php echo ($model === 'T5') ? 'selected' : ''; ?>>T5</option>
                    <option value="CodeBERT" <?php echo ($model === 'CodeBERT') ? 'selected' : ''; ?>>CodeBERT</option>
                </select>
            </form>

            <!-- History Box -->
            <!-- <div id="historyBox">
                <ul class="list-group list-group-flush">
                    <?php foreach ($history as $row): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-start">
                            <div class="me-auto">
                                <strong>You:</strong> <?= htmlspecialchars($row['prompt']) ?><br />
                                <strong>System:</strong> <?= htmlspecialchars($row['response']) ?><br />
                                <small class="text-muted"><?= $row['created_at'] ?></small>
                            </div>
                            <form method="POST" class="delete-form ms-3">
                                <input type="hidden" name="created_at"
                                    value="<?= htmlspecialchars($row['created_at']) ?>" />
                                <button type="submit" class="btn btn-sm btn-outline-secondary">Delete</button>
                            </form>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div> -->
        </div>

        <!-- Chat Body -->
        <div class="chat-body" id="chatBody">
            <?php foreach ($history as $row): ?>
                <div class="chat-msg">
                    <div><span class="chat-prompt">You:</span> <?= htmlspecialchars($row['prompt']) ?></div>
                    <div><span class="chat-response text-primary">System:</span> <?= htmlspecialchars($row['response']) ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- Chat Footer -->
        <div class="chat-footer">
            <form id="promptForm">
                <input type="hidden" id="selectedModel" name="model" value="<?php echo htmlspecialchars($model); ?>">
                <input type="text" name="prompt" id="promptInput" placeholder="Ask me anything..." required
                    autocomplete="off" />
                <input type="hidden" name="last_prompt" id="lastPrompt" />
                <input type="hidden" name="last_response" id="lastResponse" />
                <button type="submit">Send</button>
            </form>
        </div>
    </div>

    <script>
        function toggleHistory() {
            const box = document.getElementById('historyBox');
            box.style.display = box.style.display === 'block' ? 'none' : 'block';
        }

        // ✅ Delete prompt history entry
        document.querySelectorAll('.delete-form').forEach(form => {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                fetch("prompt.php", {
                    method: 'POST',
                    body: new FormData(this)
                }).then(() => {
                    // Reload the page to reflect changes
                    window.location.reload();
                }).catch(err => {
                    alert("Error deleting entry: " + err);
                });
            });
        });

        // ✅ Handle prompt submission with T5 Flask API
        document.getElementById("promptForm").addEventListener("submit", function(e) {
            e.preventDefault();
            const promptInput = document.getElementById("promptInput");
            const prompt = promptInput.value;
            promptInput.value = ""; // ✅ Clear input box


            fetch("http://127.0.0.1:5000/nl2sql", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify({
                        query: prompt,
                        model: document.getElementById("selectedModel").value
                    })

                })
                .then(res => res.json())
                .then(data => {
                    const response = data.sql || "No SQL response from backend";

                    // Store for verify form and DB insert
                    document.getElementById("lastPrompt").value = prompt;
                    document.getElementById("lastResponse").value = response;
                    document.getElementById("verifyPrompt").value = prompt;
                    document.getElementById("verifyResponse").value = response;

                    // ✅ Save to localStorage (without similarity)
                    localStorage.setItem("last_prompt", prompt);
                    localStorage.setItem("last_response", response);

                    // Dynamically add to chat body
                    const chatBody = document.getElementById("chatBody");
                    const msgDiv = document.createElement("div");
                    msgDiv.classList.add("chat-msg");

                    msgDiv.innerHTML = `
        <div><span class="chat-prompt">You:</span> ${prompt}</div>
        <div><span class="chat-response text-primary">System:</span> ${response}</div>
    `;

                    chatBody.appendChild(msgDiv);
                    chatBody.scrollTop = chatBody.scrollHeight;

                    // Save to DB via PHP
                    fetch("prompt.php", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/x-www-form-urlencoded"
                        },
                        body: new URLSearchParams({
                            prompt: prompt,
                            response: response,
                            model: document.getElementById("selectedModel").value
                        })
                    })
                })

        })

        // On page load, restore last message (if available)

        window.addEventListener("DOMContentLoaded", () => {
            // Restore last prompt-response from localStorage
            const prompt = localStorage.getItem("last_prompt");
            const response = localStorage.getItem("last_response");

            if (prompt && response) {
                const chatBody = document.getElementById("chatBody");
                const msgDiv = document.createElement("div");
                msgDiv.classList.add("chat-msg");
                msgDiv.innerHTML = `
        <div><span class="chat-prompt">You:</span> ${prompt}</div>
        <div><span class="chat-response text-primary">System:</span> ${response}</div>
    `;
                chatBody.appendChild(msgDiv);
                chatBody.scrollTop = chatBody.scrollHeight;
            }
        });


        // Persist selected model in localStorage
        document.getElementById("model").addEventListener("change", function() {
            localStorage.setItem("selected_model", this.value);
            document.getElementById("selectedModel").value = this.value;
        });
    </script>


</body>

</html>