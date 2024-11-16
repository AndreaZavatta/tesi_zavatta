
// Funzione per mostrare la tab selezionata
    function showTab(tabIndex) {
            const tabs = document.querySelectorAll('.tab-content');
            tabs.forEach((tab, index) => {
                if (index === tabIndex) {
                    tab.classList.add('active');
                } else {
                    tab.classList.remove('active');
                }
            });

            showActiveBasedOnContainer(tabIndex);

            // Salva l'indice della tab attiva nel localStorage
            localStorage.setItem('activeTab', tabIndex);
    }




    function toggleLogout() {
        const logoutButton = document.getElementsByClassName("log_button")[0];
        // Toggle visibility of the logout button
        if (logoutButton.style.display === "none") {
            logoutButton.style.display = "flex";
        } else {
            logoutButton.style.display = "none";
        }
    }

        function showActiveBasedOnContainer(tabIndex) {
            // Define pairs of indices to activate together
            const pairs = {
                0: [0, 4],
                1: [1, 5],
                2: [2, 6],
                3: [3, 7],
                4: [1, 5],
                5: [2, 6],
                6: [3, 7]
            };


                const tabButtons = Array.from(document.querySelectorAll('.tab'));

                // Get the pair of indices to activate
                const indicesToActivate = pairs[tabIndex] || [tabIndex];

                // Apply the active class based on the paired indices
                tabButtons.forEach((tab, index) => {
                    if (indicesToActivate.includes(index)) {
                        tab.classList.add('active');
                    } else {
                        tab.classList.remove('active');
                    }
                });
            }

            
    function showActiveTab(){
        const activeTab = localStorage.getItem('activeTab');
        if (activeTab !== null) {
            showTab(parseInt(activeTab));
        } else {
            showTab(0); // Se nessuna tab è salvata, mostra la prima per default
        }

        // Rendi i messaggi di errore o successo invisibili dopo 5 secondi
        const message = document.querySelector('.success-message, .error-message');
        if (message) {
            setTimeout(() => {
                message.style.display = 'none';
            }, 5000); // 5000 millisecondi = 5 secondi
        }
    }

function simulateClick(buttonId) {
    // Prevenire il comportamento di default del link
    event.preventDefault();

    // Controlla se siamo nella tab giusta
    const params = new URLSearchParams(window.location.search);
    if (params.get("tab") !== "balloting") {
        // Cambia la tab e ricarica
        window.location.href = "?tab=balloting";
        localStorage.setItem("targetButton", buttonId);
        return;
    }

    // Simula il click sul pulsante
    const button = document.getElementById(buttonId);
    if (button) {
        button.click();
    }
}

function showUserInfo(){
            document.querySelector('.profile-tab').addEventListener('click', function (event) {
            event.preventDefault(); // Prevent default navigation behavior

            const contElements = document.querySelectorAll('.cont');

            if (contElements.length > 1) {
                // Check if the second element is currently hidden
                if (contElements[1].style.display === 'none') {
                    contElements[1].style.display = 'block';
                    contElements[0].style.display = 'none';
                    // Show both elements
                } else {
                    // Hide the second element
                    contElements[1].style.display = 'none';
                    contElements[0].style.display = 'block';
                }
            }
        });
}
document.addEventListener("DOMContentLoaded", function() {
        const targetButton = localStorage.getItem("targetButton");
        if (targetButton) {
            const button = document.getElementById(targetButton);
            if (button) {
                button.click();
            }
            localStorage.removeItem("targetButton"); // Rimuovi dopo l'uso
        }
        progressInterval = '';
        showActiveTab();
        loadUsers();
        showUserInfo();

        document.getElementById('stop-import-btn').addEventListener('click', function() {
            if (confirm('Sei sicuro di voler interrompere il processo di importazione?')) {
                fetch('stop_import.php', {
                    method: 'POST'
                })
                .then(response => response.json())
                .then(data => {
                    alert(data.message); // Notify the user that the import has been stopped

                    // Optionally hide the spinner and stop polling for progress
                    document.getElementById('loading-spinner').style.display = 'none';
                    clearInterval(progressInterval); // Stop the progress update interval
                })
                .catch(error => {
                    console.error('Errore durante l\'interruzione dell\'importazione:', error);
                });
            }
        });


        function uploadFileVotazioni() {
            // Show spinner
            document.getElementById('loading-spinner').style.display = 'flex';

            // Prepare form data
            const formData = new FormData(document.getElementById('upload-form-app2'));
            progressInterval = setInterval(updateProgress, 1000); // Optional: if you have a progress bar

            fetch('upload_json.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // Change to text to log it first
            .then(data => {
                console.log(data); // Log the raw response
                const jsonData = JSON.parse(data); // Then parse the JSON
                if (jsonData.error) {
                    alert(jsonData.error); // Display error message if any
                } else {
                    console.log(`Caricamento dati... ${data.percentage}-${data.processed}-${data.total}`)
                    const progressText = document.getElementById('progress-text');
                    const percentage = data.percentage;
                    progressText.innerText = `Caricamento dati... ${percentage}%`;

                    // Optionally hide the spinner when done
                    if (percentage >= 100) {
                        document.getElementById('loading-spinner').style.display = 'none';
                        clearInterval(progressInterval); // Stop polling once complete
                    }
                }
                document.getElementById('loading-spinner').style.display = 'none'; // Hide spinner after completion
            })
            .catch(error => {
                error_log('zava Error:', error);
                document.getElementById('loading-spinner').style.display = 'none'; // Hide spinner on error
                alert('Errore durante il caricamento o la lavorazione del file.');
            });
        }

        function uploadFile() {
            // Show spinner
            document.getElementById('loading-spinner').style.display = 'flex';

            // Prepare form data
            const formData = new FormData(document.getElementById('upload-form'));
            progressInterval = setInterval(updateProgress, 1000);
            fetch('../import_table.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.text()) // Change to text to log it first
            .then(data => {
                console.log(data); // Log the raw response
                const jsonData = JSON.parse(data); // Then parse the JSON
                document.getElementById('successful-inserts').innerText = `Righe inserite: ${jsonData.successful_inserts}`;
                document.getElementById('skipped-rows').innerText = `Righe saltate: ${jsonData.skipped_rows}`;
                document.getElementById('total-rows').innerText = `Righe totali: ${jsonData.total_rows}`;
                document.getElementById('summary').style.display = 'block'; // Mostra il riepilogo
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('loading-spinner').style.display = 'none'; // Nascondi lo spinner in caso di errore
            });

        }

        // Function to fetch and update progress
        function updateProgress() {
            fetch('progress.php')
            .then(response => response.json())
            .then(data => {
                if (data.error) {
                    console.error(data.error);
                } else {
                    console.log(`Caricamento dati... ${data.percentage}-${data.processed}-${data.total}`)
                    const progressText = document.getElementById('progress-text');
                    const percentage = data.percentage;
                    progressText.innerText = `Caricamento dati... ${percentage}%`;

                    // Optionally hide the spinner when done
                    if (percentage >= 100) {
                        document.getElementById('loading-spinner').style.display = 'none';
                        clearInterval(progressInterval); // Stop polling once complete
                    }
                }
            })
            .catch(error => console.error('Error fetching progress:', error));
        }


        window.deleteAllTablesMap = function() {
            if (confirm("Sei sicuro di voler eliminare tutte le tabelle? Questa azione è irreversibile.")) {
                fetch('delete_all_tables_map.php', {
                method: 'POST'
            })
            .then(response => response.text()) // Use text() to log the raw response
            .then(data => {
                console.log(data); // Log the raw HTML response to see what the error is
                // You can handle different types of responses here, such as HTML error pages
                try {
                    const jsonData = JSON.parse(data); // Try parsing JSON if applicable
                    if (jsonData.error) {
                        alert("Errore: " + jsonData.error);
                    } else {
                        alert(jsonData.message);
                        document.getElementById('deleted-rows').innerText = `Tabelle eliminate: ${jsonData.deleted_tables}`;
                        document.getElementById('summary').style.display = 'block'; // Show summary after deletion
                    }
                } catch (err) {
                    // Handle the case where the response is not valid JSON
                    console.error('Error parsing JSON:', err);
                    alert('Errore: la risposta del server non è in formato JSON.');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert("Errore durante la richiesta di eliminazione di tutte le tabelle.");
            });

            }
        };

        window.deleteAllTablesVotazioni = function() {
            if (confirm("Sei sicuro di voler eliminare tutte le tabelle di votazioni? Questa azione è irreversibile.")) {
                fetch('delete_all_tables_votazioni.php', {
                    method: 'POST'
                })
                .then(response => response.text())
                .then(data => {
                    console.log(data); // Log the raw HTML response to see what the error is
                    try {
                        const jsonData = JSON.parse(data);
                        if (jsonData.error) {
                            alert("Errore: " + jsonData.error);
                        } else {
                            alert(jsonData.message);
                            document.getElementById('deleted-rows').innerText = `Tabelle votazioni eliminate: ${jsonData.deleted_tables}`;
                            document.getElementById('summary').style.display = 'block';
                        }
                    } catch (err) {
                        console.error('Error parsing JSON:', err);
                        alert('Errore: la risposta del server non è in formato JSON.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert("Errore durante la richiesta di eliminazione di tutte le tabelle di votazioni.");
                });
            }
        };


        // Attach to form submission
        document.getElementById("upload-form").onsubmit = function(event) {
            event.preventDefault();
            uploadFile();
        };
        document.getElementById('upload-form-app2').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the default form submission
            uploadFileVotazioni();

        });
    });


function validatePassword(newPassword, confirmNewPassword, errorDivId) {
    console.log("dentro validate password");
    const errorDiv = document.getElementById(errorDivId);

    // Verifica sicurezza della password lato client
    const regexLower = /[a-z]/;
    const regexUpper = /[A-Z]/;
    const regexNumber = /[0-9]/;
    const regexSpecial = /[\W]/;

    if (newPassword.length < 8 || !regexLower.test(newPassword) || !regexUpper.test(newPassword) || !regexNumber.test(newPassword) || !regexSpecial.test(newPassword)) {
        errorDiv.innerHTML = "La password deve contenere almeno 8 caratteri, includere una lettera maiuscola, una minuscola, un numero e un carattere speciale.";
        errorDiv.style.display = "block";
        return false;
    }

    if (newPassword !== confirmNewPassword) {
        errorDiv.innerHTML = "Le nuove password non corrispondono.";
        errorDiv.style.display = "block";
        return false;
    }

    errorDiv.style.display = "none";
    return true;
}

// Registration function
function registerUser() {
    const username = document.getElementById("username").value;
    const newPassword = document.getElementById("password").value;
    const confirmNewPassword = document.getElementById("confirm_password").value;
    const profileId = document.getElementById("profile").value;

    // Debugging: Log the input values
    console.log("Username:", username);
    console.log("New Password:", newPassword);
    console.log("Confirm Password:", confirmNewPassword);
    console.log("Profile ID:", profileId);

    if (!validatePassword(newPassword, confirmNewPassword, 'password-error-registration')) {
        console.log("Password validation failed.");
        return;
    }

    // Send registration request with username, password, and profile ID
    fetch('../LoginRegistration/registernew.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, new_password: newPassword, profile_id: profileId })
    })
    .then(response => response.json())
    .then(data => {
        console.log("Response from server:", data); // Debugging: Log the server response
        if (data.success) {
            alert("Registrazione completata con successo!");
            // Clear the form fields
            document.getElementById("username").value = '';
            document.getElementById("password").value = '';
            document.getElementById("confirm_password").value = '';
            document.getElementById("profile").value = ''; // Clear the profile selection
        } else {
            alert("Errore: " + data.error);
        }
    })
    .catch(error => {
        console.error('Errore nella richiesta:', error);
        alert("Errore durante la registrazione.");
    });
}

    function fetchPermissionDescription() {
        const permission = document.getElementById('permissions-picklist').value;

        // Make AJAX request to fetch the description
        const xhr = new XMLHttpRequest();
        xhr.open('POST', 'fetch_description.php', true);
        xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

        xhr.onload = function () {
            if (xhr.status === 200) {
                document.getElementById('permission-description').innerText = xhr.responseText;
            } else {
                document.getElementById('permission-description').innerText = 'Error fetching description.';
            }
        };

        xhr.send('permission=' + encodeURIComponent(permission));
    }

// Password update function
function updateUserPassword() {
    const oldPassword = document.getElementById("old_password").value;
    const newPassword = document.getElementById("new_password").value;
    const confirmNewPassword = document.getElementById("confirm_new_password").value;

    if (!validatePassword(newPassword, confirmNewPassword, 'password-error')) return;

    fetch('update_password.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ old_password: oldPassword, new_password: newPassword })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Password aggiornata con successo!");
            document.getElementById("old_password").value = '';
            document.getElementById("new_password").value = '';
            document.getElementById("confirm_new_password").value = '';
        } else {
            alert("Errore: " + data.error);
        }
    })
    .catch(error => {
        console.error('Errore nella richiesta:', error);
        alert("Errore durante l'aggiornamento della password.");
    });
}

function loadUsers() {
    fetch('get_users.php')
        .then(response => response.json())
        .then(data => {
            const usersTable = document.getElementById("users-table").getElementsByTagName('tbody')[0];
            usersTable.innerHTML = ""; // Clear current rows

            data.users.forEach(user => {
                const row = usersTable.insertRow();
                row.innerHTML = `
                    <td>
                        <div>
                            <p>${user.username}</p>
                            <i class="fas fa-edit" onclick="openEditUsernameModal(${user.id})" title="Edit Username" style="cursor: pointer; color: #007bff; margin-left: 10px;"></i>
                        </div>
                    <td>
                        <i class="fas fa-edit" onclick="openEditPasswordModal(${user.id})" title="Edit Password" style="cursor: pointer; color: #007bff;"></i>
                    </td>
                    <td>
                        <i class="fas fa-trash" onclick="deleteUser(${user.id})" title="Eliminazione Utentee Utente" style="cursor: pointer; color: #dc3545;"></i>
                    </td>
                `;
            });
        })
        .catch(error => console.error('Error loading users:', error));
}
// Funzione per aprire il modale per la modifica dell'username
function openEditUsernameModal(userId, currentValue = '') {
    document.getElementById("editUsernameModal").style.display = "block";
    document.getElementById("modalOverlay").style.display = "block"; // Mostra l'overlay per disabilitare il resto della pagina
    document.getElementById("edit-field-input").value = currentValue;
    document.getElementById("edit-field-input").dataset.userId = userId; // Memorizza l'ID dell'utente
}

// Funzione per aprire il modale per la modifica della password
function openEditPasswordModal(userId) {
    document.getElementById("editPasswordModal").style.display = "block";
    document.getElementById("modalOverlay").style.display = "block"; // Mostra l'overlay per disabilitare il resto della pagina
    document.getElementById("new-password").value = '';
    document.getElementById("confirm-password").value = '';
    document.getElementById("password-error-message").style.display = "none"; // Nascondi eventuali messaggi di errore
    document.getElementById("new-password").dataset.userId = userId; // Memorizza l'ID dell'utente
}

// Funzione per chiudere entrambi i modali
function closeEditModal() {
    document.getElementById("editUsernameModal").style.display = "none";
    document.getElementById("editPasswordModal").style.display = "none";
    document.getElementById("modalOverlay").style.display = "none"; // Nascondi l'overlay
}

// Funzione per salvare le modifiche all'username
function saveUsernameChanges() {
    const userId = document.getElementById("edit-field-input").dataset.userId;
    const newUsername = document.getElementById("edit-field-input").value;

    fetch(`update_user.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: userId, username: newUsername })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Username aggiornato con successo.");
            closeEditModal();
            loadUsers(); // Ricarica l'elenco degli utenti
        } else {
            alert("Errore durante l'aggiornamento dell'username: " + data.error);
        }
    })
    .catch(error => console.error('Errore durante l\'aggiornamento dell\'username:', error));
}

// Funzione per salvare le modifiche alla password
function savePasswordChanges() {
    const userId = document.getElementById("new-password").dataset.userId;
    const newPassword = document.getElementById("new-password").value;
    const confirmPassword = document.getElementById("confirm-password").value;

    // Valida la password prima di inviarla
    if (!validatePassword(newPassword, confirmPassword, "password-error-message")) {
        return;
    }

    fetch(`update_user.php`, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: userId, password: newPassword })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert("Password aggiornata con successo.");
            closeEditModal();
            loadUsers(); // Ricarica l'elenco degli utenti
        } else {
            alert("Errore durante l'aggiornamento della password: " + data.error);
        }
    })
    .catch(error => console.error('Errore durante l\'aggiornamento della password:', error));
}


// Delete a user
function deleteUser(userId) {
    if (confirm("Are you sure you want to delete this user?")) {
        fetch('delete_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: userId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert("User deleted successfully.");
                loadUsers(); // Reload users
            } else {
                alert("Error deleting user: " + data.error);
            }
        })
        .catch(error => console.error('Error deleting user:', error));
    }
}
