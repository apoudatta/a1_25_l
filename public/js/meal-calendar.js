function initMealCalendar(cfg) {
    const {
      startDate,      // string "YYYY-MM-DD"
      endDate,        // string "YYYY-MM-DD" (Ramadan mode)
      cutoffDays,     // integer (cutoff-days mode)
      cutOffTime,     // string "HH:MM:SS"
      leadDays,       // integer
      registeredDates,// [ "YYYY-MM-DD", … ]
      publicHolidays, // [ "YYYY-MM-DD", … ]
      weeklyHolidays  // [ 5, 6 ] etc.
    } = cfg;
  
    // Base dates
    const now    = new Date();
    const today  = new Date(startDate || now);
  
    // Determine cutoffDate
    let cutoffDate;
    const isRamadan = Boolean(endDate);
    if (isRamadan) {
      cutoffDate = new Date(endDate);
    } else {
      cutoffDate = new Date(today);
      cutoffDate.setDate(cutoffDate.getDate() + cutoffDays);
    }
  
    // Formatter
    const iso = d => {
      const Y = d.getFullYear(),
            M = String(d.getMonth()+1).padStart(2,'0'),
            D = String(d.getDate()   ).padStart(2,'0');
      return `${Y}-${M}-${D}`;
    };
  
    // Build availableDates
    const availableDates = [];
    for (let dt = new Date(today); dt <= cutoffDate; dt.setDate(dt.getDate()+1)) {
      const s  = iso(dt),
            wd = dt.getDay();
  
      // static disables
      if (registeredDates.includes(s)
       || publicHolidays.includes(s)
       || weeklyHolidays.includes(wd)) {
        continue;
      }
  
      // dynamic cutoff
      const cm = new Date(dt);
      cm.setDate(cm.getDate() - leadDays);
      const [h,m,sec] = cutOffTime.split(':').map(x=>parseInt(x,10));
      cm.setHours(h,m,sec);
      if (now > cm) continue;
  
      availableDates.push(s);
    }
  
    // Table updater
    function updateTable(dates) {
      const tbody = document.getElementById('selected-dates-body');
      tbody.innerHTML = '';
      if (!dates.length) {
        const tr = document.createElement('tr'),
              td = document.createElement('td');
        td.colSpan = 2;
        td.textContent = 'No date selected yet';
        tr.appendChild(td);
        tbody.appendChild(tr);
        return;
      }
      dates.sort((a,b)=>a-b).forEach((d,i)=>{
        const tr = document.createElement('tr'),
              td1= document.createElement('td'),
              td2= document.createElement('td');
        td1.textContent = i+1;
        td2.textContent = `${d.getDate()} ${d.toLocaleString('default',{month:'short'})}, ${d.getFullYear()}`;
        tr.appendChild(td1);
        tr.appendChild(td2);
        tbody.appendChild(tr);
      });
    }
  
    // Initialize Flatpickr
    const fp = flatpickr("#meal-calendar", {
      mode:   "multiple",
      dateFormat: "d/m/Y",
      defaultDate: today,
      minDate:     today,
      maxDate:     cutoffDate,
      monthSelectorType: "dropdown",
  
      disable: [ date => {
        const s  = iso(date),
              wd = date.getDay();
  
        // outside window?
        if (date < today || date > cutoffDate) return true;
  
        // static disables
        if (registeredDates.includes(s)
         || publicHolidays.includes(s)
         || weeklyHolidays.includes(wd)) return true;
  
        // dynamic cutoff
        const cm2 = new Date(date);
        cm2.setDate(cm2.getDate() - leadDays);
        const [h2,m2,sec2] = cutOffTime.split(':').map(x=>parseInt(x,10));
        cm2.setHours(h2,m2,sec2);
        return now > cm2;
      }],
  
      onDayCreate: (_d,_s,_fp,el) => {
        const d  = el.dateObj,
              s  = iso(d),
              wd = d.getDay();
  
        if (d < today || d > cutoffDate) {
          el.classList.add("cutoff-day");
          el.title = isRamadan
            ? "Outside Ramadan period"
            : `Only within ${cutoffDays} days`;
        }
        else if (registeredDates.includes(s)) {
          el.classList.add("registered-day");
          el.title = "Already Registered";
        }
        else if (publicHolidays.includes(s)) {
          el.classList.add("holiday-day");
          el.title = "Public Holiday";
        }
        else if (weeklyHolidays.includes(wd)) {
          el.classList.add("weekly-holiday-day");
          el.title = "Weekly Holiday";
        }
        else {
          const cm3 = new Date(d);
          cm3.setDate(cm3.getDate() - leadDays);
          const [h3,m3,sec3] = cutOffTime.split(':').map(x=>parseInt(x,10));
          cm3.setHours(h3,m3,sec3);
          if (now > cm3) {
            el.classList.add("cutoff-day");
            el.title = `Too late: cutoff was ${cutOffTime} (${leadDays} day(s) prior)`;
          }
        }
      },
  
      onChange:    dates => updateTable(dates),
      onReady:     (_d,_s,inst) => { injectButtons(inst); updateTable(inst.selectedDates); },
      onMonthChange:(_d,_s,inst) => injectButtons(inst),
    });
  
    // Inject buttons
    // Inject buttons
    function injectButtons(fpInst) {
      const cal    = fpInst.calendarContainer;
      const months = cal.querySelector(".flatpickr-months");
      // already injected? bail
      if (!months || cal.querySelector(".fp-select-all")) return;

      // Use a fragment so order stays correct
      const frag = document.createDocumentFragment();

      // All Days
      const btnAll = document.createElement("button");
      btnAll.type = "button";
      btnAll.textContent = "All Days";
      btnAll.className = "fp-select-all btn btn-sm btn-outline-primary";
      btnAll.onclick = () => fpInst.setDate(availableDates.map(d => new Date(d)), true);
      frag.appendChild(btnAll);

      // 7 Days
      const btn7 = document.createElement("button");
      btn7.type = "button";
      btn7.textContent = "7 Days";
      btn7.className = "fp-select-next7 btn btn-sm btn-outline-secondary ms-1";
      btn7.onclick = () => {
        const next7 = availableDates.slice(0, 7).map(d => new Date(d));
        fpInst.setDate(next7, true);
      };
      frag.appendChild(btn7);

      // 15 Days
      const btn15 = document.createElement("button");
      btn15.type = "button";
      btn15.textContent = "15 Days";
      btn15.className = "fp-select-next15 btn btn-sm btn-outline-secondary ms-1";
      btn15.onclick = () => {
        const next15 = availableDates.slice(0, 15).map(d => new Date(d));
        fpInst.setDate(next15, true);
      };
      frag.appendChild(btn15);

      // NEW: Unselect All
      const btnClear = document.createElement("button");
      btnClear.type = "button";
      btnClear.textContent = "Clear";
      btnClear.className = "fp-clear btn btn-sm btn-outline-danger ms-1";
      btnClear.onclick = () => {
        fpInst.clear();      // clears the picker
        updateTable([]);     // clears the right-side list immediately
      };
      frag.appendChild(btnClear);

      // insert once, before the month header
      months.parentNode.insertBefore(frag, months);
    }

  }
  