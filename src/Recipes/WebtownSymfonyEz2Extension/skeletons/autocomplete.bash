_get_ezsf_xml() {
    local ezsf_cache_file=${wf_directory_name}/autocomplete.ezsf.xml
    # We load everything into an xml file
    [[ ! -f $ezsf_cache_file ]] || [[ -z $(cat $ezsf_cache_file) ]] && wf ezsf list --format=xml > $ezsf_cache_file
    echo $(cat $ezsf_cache_file)
}

case $COMP_CWORD in
    1)
        # do nothing
    ;;
    2)
        # Load commands
        case ${first} in
            ezsf)
                local ezsf_cache_commands=$(_get_ezsf_xml | grep -oP "(?<=<command>)[^<]+(?=</command>)")

                words+=" ${ezsf_cache_commands:-""}"
            ;;
        esac
    ;;
    *)
        # Load command options
        case ${first} in
            ezsf)
                local ezsfcmd=${COMP_WORDS[2]}
                local ezsfcmd_cache_options=$(_get_ezsf_xml | tr '\n' '\a' | grep -oP '<command id="'$ezsfcmd'".*?</command>' | grep -oP '(?<=<option name=")[^"]+(?=")')

                words+=" ${ezsfcmd_cache_options:-""}"
            ;;
        esac
esac
