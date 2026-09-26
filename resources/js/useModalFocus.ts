import { useEffect, useRef } from 'react';

const FOCUSABLE = [
  'a[href]',
  'button:not([disabled])',
  'input:not([disabled])',
  'select:not([disabled])',
  'textarea:not([disabled])',
  '[contenteditable="true"]',
  '[tabindex]:not([tabindex="-1"])',
].join(',');

function focusableElements(container: HTMLElement) {
  return Array.from(container.querySelectorAll<HTMLElement>(FOCUSABLE)).filter(
    element => element.getAttribute('aria-hidden') !== 'true' && !element.hasAttribute('hidden'),
  );
}

export default function useModalFocus({
  onClose,
  returnFocusRef,
  closeOnEscape = true,
}: {
  onClose: () => void;
  returnFocusRef: React.RefObject<HTMLElement | null>;
  closeOnEscape?: boolean;
}) {
  const dialogRef = useRef<HTMLDivElement>(null);

  useEffect(() => {
    const dialog = dialogRef.current;
    const initial = dialog?.querySelector<HTMLElement>('[data-modal-initial-focus]') ?? (dialog ? focusableElements(dialog)[0] : null) ?? dialog;
    const focusFrame = window.requestAnimationFrame(() => initial?.focus({ preventScroll: true }));
    document.body.classList.add('cms-modal-open');

    return () => {
      window.cancelAnimationFrame(focusFrame);
      document.body.classList.remove('cms-modal-open');
      const opener = returnFocusRef.current;
      window.requestAnimationFrame(() => {
        if (opener?.isConnected) opener.focus({ preventScroll: true });
      });
    };
  }, [returnFocusRef]);

  const onKeyDown = (event: React.KeyboardEvent<HTMLDivElement>) => {
    if (event.key === 'Escape' && closeOnEscape) {
      event.preventDefault();
      event.stopPropagation();
      onClose();
      return;
    }

    if (event.key !== 'Tab' || !dialogRef.current) return;
    const focusable = focusableElements(dialogRef.current);
    if (!focusable.length) {
      event.preventDefault();
      dialogRef.current.focus();
      return;
    }

    const first = focusable[0];
    const last = focusable[focusable.length - 1];
    const active = document.activeElement;
    if (event.shiftKey && (active === first || !dialogRef.current.contains(active))) {
      event.preventDefault();
      last.focus();
    } else if (!event.shiftKey && (active === last || !dialogRef.current.contains(active))) {
      event.preventDefault();
      first.focus();
    }
  };

  return { dialogRef, onKeyDown };
}
