document.addEventListener("DOMContentLoaded", function () {
    // 下拉選單邏輯
    const dropdowns = document.querySelectorAll(".dropdown-menu-container");
    dropdowns.forEach((dropdown) => {
        const button = dropdown.querySelector(".dropdown-button");
        button.addEventListener("click", function (e) {
            e.stopPropagation();
            dropdown.classList.toggle("active");
        });
    });

    document.addEventListener("click", function () {
        dropdowns.forEach((dropdown) => dropdown.classList.remove("active"));
    });

    // 文章編輯邏輯
    document.querySelectorAll(".edit-post").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            const postId = this.getAttribute("data-post-id");
            const newContent = prompt("請輸入新的文章內容：");
            if (newContent) {
                fetch("edit_post.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `post_id=${postId}&new_content=${encodeURIComponent(newContent)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "文章更新成功") {
                        alert("文章已更新！");
                        location.reload();
                    } else {
                        alert("文章更新失敗：" + data);
                    }
                });
            }
        });
    });

    // 留言編輯邏輯
    document.querySelectorAll(".edit-comment").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            const commentId = this.getAttribute("data-comment-id");
            const newContent = prompt("請輸入新的留言內容：");
            if (newContent) {
                fetch("edit_comment.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `comment_id=${commentId}&new_content=${encodeURIComponent(newContent)}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "留言更新成功") {
                        alert("留言已更新！");
                        location.reload();
                    } else {
                        alert("留言更新失敗：" + data);
                    }
                });
            }
        });
    });

    // 文章刪除邏輯
    document.querySelectorAll(".delete-post").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            if (confirm("確定要刪除這篇文章嗎？")) {
                const postId = this.getAttribute("data-post-id");
                fetch("delete_post.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `post_id=${postId}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "文章刪除成功") {
                        alert("文章已刪除！");
                        window.location.href = "index.php";
                    } else {
                        alert("文章刪除失敗：" + data);
                    }
                });
            }
        });
    });

    // 留言刪除邏輯
    document.querySelectorAll(".delete-comment").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();
            if (confirm("確定要刪除此留言嗎？")) {
                const commentId = this.getAttribute("data-comment-id");
                fetch("delete_comment.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/x-www-form-urlencoded"
                    },
                    body: `comment_id=${commentId}`
                })
                .then(response => response.text())
                .then(data => {
                    if (data === "留言刪除成功") {
                        alert("留言已刪除！");
                        location.reload();
                    } else {
                        alert("留言刪除失敗：" + data);
                    }
                });
            }
        });
    });
});
