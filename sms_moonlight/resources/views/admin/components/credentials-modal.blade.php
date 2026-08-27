@php
    $contentId = 'createdCredentialsContent-' . uniqid();
    $usernameId = $contentId . '-username';
    $passwordId = $contentId . '-password';
@endphp

<div id="{{ $contentId }}" class="space-y-4">
    <p class="text-2xs text-slate-500 dark:text-slate-400">
        Copy these credentials before closing this modal.
    </p>

    @if(! empty($credentials['name']))
        <div>
            <div class="form-label">Name</div>
            <div class="font-semibold">{{ $credentials['name'] }}</div>
        </div>
    @endif

    <div>
        <label class="form-label" for="{{ $usernameId }}">Username</label>
        <input
            id="{{ $usernameId }}"
            type="text"
            readonly
            value="{{ $credentials['username'] ?? '' }}"
            class="form-input"
        >
    </div>

    <div>
        <label class="form-label" for="{{ $passwordId }}">Password</label>
        <input
            id="{{ $passwordId }}"
            type="text"
            readonly
            value="{{ $credentials['password'] ?? '' }}"
            class="form-input"
        >
    </div>

    <div class="flex justify-end">
        <button
            type="button"
            class="btn btn-primary"
            onclick="copyCredentialValues('{{ $usernameId }}', '{{ $passwordId }}', this)"
        >
            Copy
        </button>
    </div>
</div>

<script>
    window.copyCredentialValues = window.copyCredentialValues || function(usernameId, passwordId, button) {
        const username = document.getElementById(usernameId);
        const password = document.getElementById(passwordId);

        if (!username || !password) {
            return;
        }

        const value = `Username: ${username.value}\nPassword: ${password.value}`;

        const done = () => {
            const original = button.textContent;
            button.textContent = 'Copied';
            setTimeout(() => {
                button.textContent = original;
            }, 1200);
        };

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(value).then(done);
            return;
        }

        const textarea = document.createElement('textarea');
        textarea.value = value;
        textarea.setAttribute('readonly', 'readonly');
        textarea.style.position = 'fixed';
        textarea.style.opacity = '0';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        textarea.remove();
        done();
    };
</script>
