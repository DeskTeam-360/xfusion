/**
 * Simple PDF Upload with AJAX
 * Usage: Include this script and call uploadPdfResult() function
 */

function uploadPdfResult(pdfFile, userId, comment = '', options = {}) {
    return new Promise((resolve, reject) => {
        // Validate inputs
        if (!pdfFile) {
            reject(new Error('PDF file is required'));
            return;
        }
        
        if (!userId) {
            reject(new Error('User ID is required'));
            return;
        }
        
        // Check file type
        if (pdfFile.type !== 'application/pdf') {
            reject(new Error('File must be a PDF'));
            return;
        }
        
        // Check file size (10MB max)
        if (pdfFile.size > 10 * 1024 * 1024) {
            reject(new Error('File size must be less than 10MB'));
            return;
        }
        
        // Create FormData
        const formData = new FormData();
        formData.append('pdf_result', pdfFile);
        formData.append('user_id', userId);
        formData.append('comment', comment);
        
        // Create XMLHttpRequest
        const xhr = new XMLHttpRequest();
        
        // Set up progress tracking if callback provided
        if (options.onProgress) {
            xhr.upload.addEventListener('progress', function(e) {
                if (e.lengthComputable) {
                    const percentComplete = (e.loaded / e.total) * 100;
                    options.onProgress(percentComplete);
                }
            });
        }
        
        // Handle response
        xhr.addEventListener('load', function() {
            if (xhr.status === 200) {
                try {
                    const response = JSON.parse(xhr.responseText);
                    if (response.success) {
                        resolve(response.data);
                    } else {
                        reject(new Error(response.message || 'Upload failed'));
                    }
                } catch (e) {
                    reject(new Error('Invalid response from server'));
                }
            } else {
                try {
                    const response = JSON.parse(xhr.responseText);
                    reject(new Error(response.message || 'Upload failed'));
                } catch (e) {
                    reject(new Error('Upload failed with status: ' + xhr.status));
                }
            }
        });
        
        // Handle errors
        xhr.addEventListener('error', function() {
            reject(new Error('Network error occurred'));
        });
        
        // Handle abort
        xhr.addEventListener('abort', function() {
            reject(new Error('Upload was cancelled'));
        });
        
        // Start upload
        xhr.open('POST', '/api/save-pdf-result');
        xhr.send(formData);
    });
}

// Example usage with jQuery (if available)
if (typeof $ !== 'undefined') {
    $.uploadPdfResult = function(pdfFile, userId, comment, options) {
        return uploadPdfResult(pdfFile, userId, comment, options);
    };
}

// Example usage with vanilla JavaScript
window.uploadPdfResult = uploadPdfResult;

// Example HTML form integration
function setupPdfUploadForm(formId, options = {}) {
    const form = document.getElementById(formId);
    if (!form) {
        console.error('Form with ID "' + formId + '" not found');
        return;
    }
    
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const pdfInput = form.querySelector('input[type="file"]');
        const userIdInput = form.querySelector('input[name="user_id"]');
        const commentInput = form.querySelector('textarea[name="comment"]');
        
        if (!pdfInput || !pdfInput.files[0]) {
            alert('Please select a PDF file');
            return;
        }
        
        if (!userIdInput || !userIdInput.value) {
            alert('Please enter User ID');
            return;
        }
        
        const submitBtn = form.querySelector('button[type="submit"]');
        const originalText = submitBtn.textContent;
        
        // Disable form and show loading
        submitBtn.disabled = true;
        submitBtn.textContent = 'Uploading...';
        
        // Upload file
        uploadPdfResult(
            pdfInput.files[0],
            userIdInput.value,
            commentInput ? commentInput.value : '',
            {
                onProgress: function(percent) {
                    if (options.onProgress) {
                        options.onProgress(percent);
                    }
                }
            }
        ).then(function(data) {
            // Success
            if (options.onSuccess) {
                options.onSuccess(data);
            } else {
                alert('PDF uploaded successfully!');
                form.reset();
            }
        }).catch(function(error) {
            // Error
            if (options.onError) {
                options.onError(error);
            } else {
                alert('Upload failed: ' + error.message);
            }
        }).finally(function() {
            // Re-enable form
            submitBtn.disabled = false;
            submitBtn.textContent = originalText;
        });
    });
}
