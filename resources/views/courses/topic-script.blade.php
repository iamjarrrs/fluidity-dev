{{-- Tambah/hapus baris bab dan validasi ukuran file. $nextIndex = indeks form untuk bab baru berikutnya. --}}
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const container = document.getElementById('topics-container');
        const template = document.getElementById('topic-template');
        let nextIndex = {{ (int) $nextIndex }};

        // Nomor bab mengikuti urutan tampil; indeks form boleh berlubang setelah ada yang dihapus
        const renumber = () => container.querySelectorAll('.topic-number').forEach((el, n) => el.textContent = `Bab ${n + 1}`);

        container.addEventListener('click', function (e) {
            const btn = e.target.closest('.remove-topic-btn');
            if (!btn) return;
            btn.closest('.topic-item').remove();
            renumber();
        });

        document.getElementById('add-topic-btn').addEventListener('click', function () {
            container.insertAdjacentHTML('beforeend', template.innerHTML.replaceAll('__I__', nextIndex++));
            const row = container.lastElementChild;
            attachValidation(row.querySelector('.module-file-input'));
            renumber();
            row.querySelector('input[type=text]').focus();
        });

        // Validasi ukuran tiap file (maks. 5 MB per file)
        function attachValidation(input) {
            if (!input) return;
            input.addEventListener('change', function () {
                const errorText = this.parentElement.querySelector('.file-size-error');
                const listText = this.parentElement.querySelector('.selected-files-list');
                const files = Array.from(this.files || []);
                const oversized = files.find(f => f.size / (1024 * 1024) > 5);

                if (oversized) {
                    const mb = (oversized.size / (1024 * 1024)).toFixed(2).replace('.', ',');
                    errorText.textContent = `File "${oversized.name}" ${mb} MB, melebihi batas 5 MB per file`;
                    errorText.classList.remove('hidden');
                    listText.classList.add('hidden');
                    this.value = '';
                } else {
                    errorText.classList.add('hidden');
                    listText.textContent = files.length ? `${files.length} file dipilih: ${files.map(f => f.name).join(', ')}` : '';
                    listText.classList.toggle('hidden', files.length === 0);
                }
            });
        }

        container.querySelectorAll('.module-file-input').forEach(attachValidation);
    });
</script>
