(() => {
  'use strict';
  const form = document.querySelector('.project-form');
  if (!form || form.dataset.projectFormReady === 'true') return;
  form.dataset.projectFormReady = 'true';
  const input = (labelText, name, options = {}) => {
    const label = document.createElement('label');
    label.append(document.createTextNode(labelText));
    const field = document.createElement('input');
    field.name = name;
    field.type = options.type || 'text';
    if (options.maxlength) field.maxLength = options.maxlength;
    field.placeholder = options.placeholder || '';
    label.append(field);
    return label;
  };
  form.querySelectorAll('[data-project-repeater]').forEach(group => {
    const rows = group.querySelector('.project-entry-rows');
    const add = group.querySelector('[data-project-add]');
    const kind = group.dataset.projectRepeater;
    const limit = Number(group.dataset.limit);
    const renumber = () => {
      [...rows.children].forEach((row, index) => {
        row.querySelectorAll('[name]').forEach(field => {
          field.name = field.name.replace(/\[\d+\]/, '[' + index + ']');
        });
      });
      add.disabled = rows.children.length >= limit;
      group.querySelector('.project-entry-count').textContent = rows.children.length + ' / ' + limit + ' entries';
    };
    const create = () => {
      const row = document.createElement('div');
      row.className = 'project-entry-row';
      if (kind === 'amenities') row.append(input('Amenity', 'amenities[0]', {maxlength: 100, placeholder: 'e.g. Security, gym, swimming pool'}));
      if (kind === 'nearest_locations') {
        row.classList.add('is-nearby');
        row.append(input('Place name', 'nearest_locations[0][name]', {maxlength: 150, placeholder: 'e.g. Hospital, school, motorway'}), input('Distance / travel time', 'nearest_locations[0][distance]', {maxlength: 80, placeholder: 'e.g. 2 km / 5 minutes'}));
      }
      if (kind === 'videos') {
        row.classList.add('is-video');
        row.append(input('Video title', 'videos[0][title]', {maxlength: 150, placeholder: 'e.g. Project walkthrough'}), input('YouTube link', 'videos[0][url]', {type: 'url', maxlength: 500, placeholder: 'https://www.youtube.com/watch?v=...'}));
        const label = document.createElement('label');
        label.append(document.createTextNode('Video type'));
        const select = document.createElement('select');
        select.name = 'videos[0][type]';
        select.dataset.nativeSelect = '';
        [['video', 'Video'], ['short', 'Short']].forEach(([value, text]) => select.add(new Option(text, value)));
        label.append(select);
        row.append(label);
      }
      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'property-entry-button';
      remove.dataset.projectRemove = '';
      remove.textContent = 'Remove';
      remove.setAttribute('aria-label', 'Remove ' + (kind === 'videos' ? 'video' : kind === 'amenities' ? 'amenity' : 'nearby location'));
      row.append(remove);
      return row;
    };
    add.addEventListener('click', () => {
      if (rows.children.length >= limit) return;
      const row = create();
      rows.append(row);
      renumber();
      row.querySelector('input')?.focus();
    });
    rows.addEventListener('click', event => {
      const button = event.target.closest('[data-project-remove]');
      if (!button) return;
      const row = button.closest('.project-entry-row');
      const next = row.nextElementSibling || row.previousElementSibling;
      row.remove();
      renumber();
      (next?.querySelector('input') || add).focus();
    });
    form.addEventListener('submit', renumber);
    renumber();
  });
  form.querySelectorAll('[data-project-preview]').forEach(field => {
    let objectURLs = [];
    const preview = field.closest('.project-cover-upload,.project-gallery-upload,.project-document-upload').querySelector('.project-file-previews');
    const initial = preview.innerHTML;
    field.addEventListener('change', () => {
      objectURLs.forEach(url => URL.revokeObjectURL(url));
      objectURLs = [];
      preview.replaceChildren();
      const files = [...field.files];
      const isCover = field.dataset.projectPreview === 'cover';
      const maxCount = isCover ? 1 : 5;
      let message = '';
      if (files.length > maxCount) message = 'Choose up to ' + maxCount + ' files.';
      for (const file of files) {
        if (file.size > (isCover ? 5 : 10) * 1024 * 1024) message = 'Each file must be up to ' + (isCover ? 5 : 10) + ' MB.';
        if (!['image/jpeg', 'image/png', 'image/webp', ...(field.dataset.projectPreview === 'documents' ? ['application/pdf'] : [])].includes(file.type)) message = 'Choose a supported image' + (field.dataset.projectPreview === 'documents' ? ' or PDF' : '') + ' file.';
      }
      field.setCustomValidity(message);
      if (message) {
        const error = document.createElement('p');
        error.className = 'project-upload-error';
        error.textContent = message;
        preview.append(error);
        field.reportValidity();
        return;
      }
      if (!files.length) { preview.innerHTML = initial; return; }
      files.forEach(file => {
        const figure = document.createElement('figure');
        if (file.type.startsWith('image/')) {
          const image = document.createElement('img');
          const url = URL.createObjectURL(file);
          objectURLs.push(url);
          image.src = url;
          image.alt = 'Selected image preview';
          if (isCover) image.className = 'project-cover-preview';
          figure.append(image);
        } else {
          const badge = document.createElement('span');
          badge.className = 'project-file-type';
          badge.textContent = 'PDF';
          figure.append(badge);
        }
        const caption = document.createElement('figcaption');
        caption.textContent = file.name + ' · ' + (file.size / 1024 / 1024).toFixed(1) + ' MB';
        figure.append(caption);
        preview.append(figure);
      });
    });
  });
})();
